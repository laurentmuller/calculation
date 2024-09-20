<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Controller\CspReportController;
use App\Service\NonceService;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Listener to add content security policy (CSP) to response.
 *
 * @see https://mathiasbynens.be/notes/csp-reports
 * @see https://securityheaders.com/
 * @see https://github.com/aidantwoods/SecureHeaders
 * @see https://www.sentrium.co.uk/labs/application-security-101-http-headers
 */
class ResponseListener
{
    /**
     * The development firewall name.
     */
    public const FIREWALL_DEV = 'dev';

    /**
     * The main firewall name.
     */
    public const FIREWALL_MAIN = 'main';

    /**
     * The header keys for CSP value.
     */
    private const CSP_HEADERS = [
        'X-WebKit-CSP',
        'Content-Security-Policy',
        'X-Content-Security-Policy',
    ];

    /**
     * The default headers to add.
     */
    private const DEFAULT_HEADERS = [
        'referrer-policy' => 'same-origin',
        'X-FRAME-OPTIONS' => 'sameorigin',
        'X-Content-Type-Options' => 'nosniff',
        'x-permitted-cross-domain-policies' => 'none',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/csp.%kernel.environment%.json')]
        private readonly string $file,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Target('calculation.service.response')]
        private readonly CacheInterface $cache,
        private readonly UrlGeneratorInterface $generator,
        private readonly NonceService $service,
        private readonly Security $security
    ) {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->debug && $this->isDevFirewall($event->getRequest())) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->add(self::DEFAULT_HEADERS);

        $csp = $this->buildCSP();
        if ('' === $csp) {
            return;
        }

        foreach (self::CSP_HEADERS as $key) {
            $headers->set($key, $csp);
        }
    }

    private function buildCSP(): string
    {
        $csp = $this->getCSP();
        if ([] === $csp) {
            return '';
        }

        $csp = $this->replaceNonce($csp);
        $csp = $this->reduceValues($csp);

        return \implode('', $csp);
    }

    /**
     * @psalm-return array<string, string[]>
     */
    private function getCSP(): array
    {
        return $this->cache->get(
            'csp_content',
            fn (ItemInterface $item, bool &$save): array => $this->loadCSP($item, $save)
        );
    }

    private function getReportURL(): string
    {
        return $this->generator->generate(CspReportController::ROUTE_NAME, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function isDevFirewall(Request $request): bool
    {
        return self::FIREWALL_DEV === $this->security->getFirewallConfig($request)?->getName();
    }

    /**
     * @psalm-return array<string, string[]>
     */
    private function loadCSP(ItemInterface $item, bool &$save): array
    {
        $save = false;
        if (!FileUtils::exists($this->file)) {
            return [];
        }

        try {
            /** @psalm-var array<string, string[]> $content */
            $content = FileUtils::decodeJson($this->file);
            $content = $this->replaceValues($content);
            $item->set($content);
            $save = true;

            return $content;
        } catch (\InvalidArgumentException) {
            return [];
        }
    }

    /**
     * @psalm-param array<string, string[]> $array
     *
     * @psalm-return string[]
     */
    private function reduceValues(array $array): array
    {
        return \array_map(
            static fn (string $key, array $values): string => \sprintf('%s %s;', $key, \implode(' ', $values)),
            \array_keys($array),
            \array_values($array)
        );
    }

    /**
     * @psalm-param array<string, string[]> $array
     *
     * @psalm-return array<string, string[]>
     */
    private function replaceNonce(array $array): array
    {
        $nonce = $this->service->getCspNonce();

        return \array_map(fn (array $subject): array => \str_replace('nonce', $nonce, $subject), $array);
    }

    /**
     * @psalm-param array<string, string[]> $array
     *
     * @psalm-return array<string, string[]>
     */
    private function replaceValues(array $array): array
    {
        $values = [
            'none' => "'none'",
            'self' => "'self'",
            'unsafe-inline' => "'unsafe-inline'",
            'report' => $this->getReportURL(),
        ];

        return \array_map(fn (array $subject): array => StringUtils::replace($values, $subject), $array);
    }
}
