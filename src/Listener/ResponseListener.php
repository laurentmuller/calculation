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
use App\Security\SecurityAttributes;
use App\Service\NonceService;
use App\Utils\FileUtils;
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
 * @see https://developer.chrome.com/docs/lighthouse/best-practices/has-hsts
 */
class ResponseListener
{
    // the CSP header key
    private const CSP_HEADER = 'Content-Security-Policy';

    // the default headers to add
    private const DEFAULT_HEADERS = [
        'Referrer-Policy' => 'same-origin',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'X-Content-Type-Options' => 'nosniff',
        'X-Permitted-Cross-Domain-Policies' => 'none',
    ];

    // the Nonce search parameter
    private const NONCE_PARAMETER = '%nonce%';

    // the Report search parameter
    private const REPORT_PARAMETER = '%report%';

    // the headers to add for secure request.
    private const SECURE_HEADERS = [
        'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains; preload',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/csp.%kernel.environment%.json')]
        private readonly string $file,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Target('calculation.response')]
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

        $request = $event->getRequest();
        if ($this->isDevFirewall($request)) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->add(self::DEFAULT_HEADERS);
        if ($request->isSecure()) {
            $headers->add(self::SECURE_HEADERS);
        }

        $csp = $this->buildCSP();
        if (null !== $csp) {
            $headers->set(self::CSP_HEADER, $csp);
        }
    }

    private function buildCSP(): ?string
    {
        $csp = $this->getCSP();

        return null === $csp ? null : $this->replaceNonce($csp);
    }

    private function getCSP(): ?string
    {
        return $this->cache->get(
            'csp_content',
            fn (ItemInterface $item, bool &$save): ?string => $this->loadCSP($save)
        );
    }

    private function getFirewallName(Request $request): ?string
    {
        return $this->security->getFirewallConfig($request)?->getName();
    }

    private function isDevFirewall(Request $request): bool
    {
        return $this->debug && SecurityAttributes::DEV_FIREWALL === $this->getFirewallName($request);
    }

    private function loadCSP(bool &$save): ?string
    {
        $save = false;
        if (!FileUtils::exists($this->file)) {
            return null;
        }

        try {
            /** @var array<string, string[]> $content */
            $content = FileUtils::decodeJson($this->file);
            $content = $this->replaceReportUrl($content);
            $content = $this->reduceValues($content);
            $csp = \implode('', $content);
            $save = true;

            return $csp;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array<string, string[]> $array
     *
     * @return string[]
     */
    private function reduceValues(array $array): array
    {
        return \array_map(
            static fn (string $key, array $values): string => \sprintf('%s %s;', $key, \implode(' ', $values)),
            \array_keys($array),
            \array_values($array)
        );
    }

    private function replaceNonce(string $csp): string
    {
        return \str_replace(self::NONCE_PARAMETER, $this->service->getCspNonce(), $csp);
    }

    /**
     * @param array<string, string[]> $array
     *
     * @return array<string, string[]>
     */
    private function replaceReportUrl(array $array): array
    {
        $reportUrl = $this->generator->generate(
            name: CspReportController::ROUTE_NAME,
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );

        return \array_map(
            static fn (array $subject): array => \str_replace(self::REPORT_PARAMETER, $reportUrl, $subject),
            $array
        );
    }
}
