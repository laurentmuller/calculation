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

use App\Interfaces\MimeTypeInterface;
use App\Service\NonceService;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Response subscriber to add content security policy (CSP).
 *
 * For CSP violation see https://mathiasbynens.be/notes/csp-reports.
 */
#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onKernelResponse')]
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
     * The CSP none directive.
     */
    private const CSP_NONE = "'none'";

    /**
     * The CSP self directive.
     */
    private const CSP_SELF = "'self'";

    /**
     * The unsafe inline CSP directive.
     */
    private const CSP_UNSAFE_INLINE = "'unsafe-inline'";

    /**
     * The default headers to add.
     *
     * @see https://www.dareboost.com
     * @see https://securityheaders.com/
     * @see https://github.com/aidantwoods/SecureHeaders
     */
    private const DEFAULT_HEADERS = [
        'referrer-policy' => 'same-origin',
        'X-FRAME-OPTIONS' => 'sameorigin',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'x-permitted-cross-domain-policies' => self::CSP_NONE,
    ];

    /**
     * The debug firewall pattern.
     */
    private const DEV_PATTERN = '/^\/(_(profiler|wdt)|css|images|js)\//mi';

    /**
     * The CSP directives.
     *
     * @var array<string, string[]>
     */
    private readonly array $csp;

    /**
     * @throws \Exception
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/csp.%kernel.environment%.json')]
        string $file,
        NonceService $service,
        UrlGeneratorInterface $generator,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        private readonly CacheItemPoolInterface $cache
    ) {
        $this->csp = $this->loadCSP($file, $service, $generator);
    }

    /**
     * Handle kernel response event.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        // master request ?
        if (!$event->isMainRequest()) {
            return;
        }

        // development firewall ?
        $request = $event->getRequest();
        if ($this->debug && $this->isDevRequest($request)) {
            return;
        }

        // CSP
        $response = $event->getResponse();
        $headers = $response->headers;
        $csp = $this->getCSP($response);
        if ('' !== $csp) {
            foreach (self::CSP_HEADERS as $key) {
                $headers->set($key, $csp);
            }
        }
        foreach (self::DEFAULT_HEADERS as $key => $value) {
            $headers->set($key, $value);
        }
    }

    /**
     * Build the content security policy.
     */
    private function getCSP(Response $response): string
    {
        $csp = $this->csp;
        if ([] === $csp) {
            return '';
        }

        if ($response instanceof MimeTypeInterface) {
            $csp['object-src'] = [self::CSP_SELF];
            $csp['plugin-types'] = [$response->getInlineMimeType()];
        }

        return \array_reduce(
            \array_keys($csp),
            fn (string $carry, string $key): string => \sprintf('%s%s %s;', $carry, $key, \implode(' ', $csp[$key])),
            ''
        );
    }

    /**
     * Returns if the given request is from development.
     */
    private function isDevRequest(Request $request): bool
    {
        return 1 === \preg_match(self::DEV_PATTERN, $request->getRequestUri());
    }

    /**
     * Load the CSP definitions.
     *
     * @return array<string, string[]>
     */
    private function loadCSP(string $file, NonceService $service, UrlGeneratorInterface $generator): array
    {
        try {
            $item = $this->cache->getItem('csp_file');
            if ($item->isHit()) {
                return $this->replaceNonce($service, $item->get());
            }

            if (!FileUtils::exists($file)) {
                return [];
            }

            /* @psalm-var array<string, string|string[]> $csp */
            $csp = FileUtils::decodeJson($file);

            /** @psalm-var array<string, string[]> $csp */
            $csp = \array_map(static fn (string|array $value): array => (array) $value, $csp);

            $values = [
                '%report%' => $generator->generate(name: 'log_csp', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
                'unsafe-inline' => self::CSP_UNSAFE_INLINE,
                'none' => self::CSP_NONE,
                'self' => self::CSP_SELF,
            ];
            $value = \array_map(static fn (array $subject): array => StringUtils::replace($values, $subject), $csp);
            $item->set($value);
            $this->cache->save($item);

            return $this->replaceNonce($service, $value);
        } catch (\Psr\Cache\InvalidArgumentException|\InvalidArgumentException|\Exception) {
            return [];
        }
    }

    /**
     * @return array<string, string[]>
     *
     * @throws \Exception
     */
    private function replaceNonce(NonceService $service, mixed $value): array
    {
        $nonce = $service->getCspNonce();
        $encoded = \str_replace('%nonce%', $nonce, (string) \json_encode($value));
        /** @psalm-var array<string, string[]> $decoded */
        $decoded = \json_decode($encoded, true);

        return $decoded;
    }
}
