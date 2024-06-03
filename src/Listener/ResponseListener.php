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
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Response subscriber to add content security policy (CSP).
 *
 * For CSP violation see https://mathiasbynens.be/notes/csp-reports.
 */
class ResponseListener
{
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
     *
     * @see https://securityheaders.com/
     * @see https://github.com/aidantwoods/SecureHeaders
     * @see https://www.sentrium.co.uk/labs/application-security-101-http-headers
     */
    private const DEFAULT_HEADERS = [
        'referrer-policy' => 'same-origin',
        'X-FRAME-OPTIONS' => 'sameorigin',
        'X-Content-Type-Options' => 'nosniff',
        'x-permitted-cross-domain-policies' => 'none',
    ];

    /**
     * The debug firewall pattern.
     */
    private const DEV_PATTERN = '/^\/(_(profiler|wdt)|css|images|js)\//mi';

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/csp.%kernel.environment%.json')]
        private readonly string $file,
        private readonly UrlGeneratorInterface $generator,
        private readonly NonceService $service,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Target('calculation.service.response')]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @throws \Exception|InvalidArgumentException
     */
    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->debug && $this->isDevRequest($event->getRequest())) {
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

    /**
     * @throws InvalidArgumentException
     */
    private function buildCSP(): string
    {
        $csp = $this->getCSP();
        if ([] === $csp) {
            return '';
        }

        $values = ['nonce' => $this->service->getCspNonce()];
        $result = \array_map(
            static fn (array $subject): array => StringUtils::replace($values, $subject),
            $csp
        );

        return \array_reduce(
            \array_keys($result),
            fn (string $carry, string $key): string => \sprintf('%s%s %s;', $carry, $key, \implode(' ', $result[$key])),
            ''
        );
    }

    /**
     * @return array<string, string[]>
     *
     * @throws InvalidArgumentException
     */
    private function getCSP(): array
    {
        return $this->cache->get('csp_content', function (): array {
            if (!FileUtils::exists($this->file)) {
                return [];
            }

            $content = $this->loadFile();
            $values = [
                'none' => "'none'",
                'self' => "'self'",
                'unsafe-inline' => "'unsafe-inline'",
                'report' => $this->getReportURL(),
            ];

            return \array_map(
                static fn (array $subject): array => StringUtils::replace($values, $subject),
                $content
            );
        });
    }

    private function getReportURL(): string
    {
        return $this->generator->generate(CspReportController::ROUTE_NAME, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function isDevRequest(Request $request): bool
    {
        return 1 === \preg_match(self::DEV_PATTERN, $request->getRequestUri());
    }

    /**
     * @psalm-return array<string, string[]>
     */
    private function loadFile(): array
    {
        /* @psalm-var array<string, string|string[]> $content */
        $content = FileUtils::decodeJson($this->file);

        /** @psalm-var array<string, string[]> */
        return \array_map(
            static fn (string|array $value): array => (array) $value,
            $content
        );
    }
}
