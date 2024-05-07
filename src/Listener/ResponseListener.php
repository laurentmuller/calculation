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
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * The CSP nonce parameter.
     */
    private const CSP_NONCE_PARAM = '%nonce%';

    /**
     * The CSP none parameter.
     */
    private const CSP_NONE_PARAM = 'none';

    /**
     * The CSP none directive value.
     */
    private const CSP_NONE_VALUE = "'none'";

    /**
     * The CSP report parameter.
     */
    private const CSP_REPORT_PARAM = '%report%';

    /**
     * The CSP self directive parameter.
     */
    private const CSP_SELF_PARAM = 'self';

    /**
     * The CSP self directive value.
     */
    private const CSP_SELF_VALUE = "'self'";

    /**
     * The unsafe inline CSP parameter name.
     */
    private const CSP_UNSAFE_INLINE_PARAM = 'unsafe-inline';

    /**
     * The CSP unsafe inline directive value.
     */
    private const CSP_UNSAFE_INLINE_VALUE = "'unsafe-inline'";

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
        'x-permitted-cross-domain-policies' => self::CSP_NONE_VALUE,
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
        #[Target('cache.calculation.service.response')]
        private readonly CacheInterface $cache,
        #[Autowire('%kernel.project_dir%/public')]
        private readonly string $publicDir
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

        if ($this->debug && $this->handleDebug($event)) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;
        $headers->add(self::DEFAULT_HEADERS);

        $csp = $this->buildCSP($response);
        if ('' === $csp) {
            return;
        }

        foreach (self::CSP_HEADERS as $key) {
            $headers->set($key, $csp);
        }
    }

    /**
     * Build the content security policy.
     *
     * @throws \Exception|InvalidArgumentException
     */
    private function buildCSP(Response $response): string
    {
        $csp = $this->getCSP();
        if ([] === $csp) {
            return '';
        }

        if ($response instanceof MimeTypeInterface) {
            $csp['object-src'] = [self::CSP_SELF_VALUE];
            $csp['plugin-types'] = [$response->getInlineMimeType()];
        }

        $this->replaceNonce($csp);

        return \array_reduce(
            \array_keys($csp),
            fn (string $carry, string $key): string => \sprintf('%s%s %s;', $carry, $key, \implode(' ', $csp[$key])),
            ''
        );
    }

    /**
     * Gets the CSP definitions.
     *
     * @return array<string, string[]>
     *
     * @throws InvalidArgumentException
     */
    private function getCSP(): array
    {
        return $this->cache->get('csp_content', function () {
            if (!FileUtils::exists($this->file)) {
                return [];
            }

            /* @psalm-var array<string, string|string[]> $content */
            $content = FileUtils::decodeJson($this->file);

            /** @psalm-var array<string, string[]> $csp */
            $csp = \array_map(static fn (string|array $value): array => (array) $value, $content);

            $values = [
                self::CSP_REPORT_PARAM => $this->getReportURL(),
                self::CSP_NONE_PARAM => self::CSP_NONE_VALUE,
                self::CSP_SELF_PARAM => self::CSP_SELF_VALUE,
                self::CSP_UNSAFE_INLINE_PARAM => self::CSP_UNSAFE_INLINE_VALUE,
            ];

            return \array_map(static fn (array $subject): array => StringUtils::replace($values, $subject), $csp);
        });
    }

    private function getReportURL(): string
    {
        return $this->generator->generate(name: 'log_csp', referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function handleDebug(ResponseEvent $event): bool
    {
        $request = $event->getRequest();
        if ($this->isDevRequest($request)) {
            return true;
        }

        $name = \basename($request->getPathInfo());
        $ext = Path::getExtension($name);
        if (!\in_array($ext, ['css', 'ttf', 'woff2'], true)) {
            return false;
        }

        return $this->updateResponse($event, $name);
    }

    /**
     * Returns if the given request is from development.
     */
    private function isDevRequest(Request $request): bool
    {
        return 1 === \preg_match(self::DEV_PATTERN, $request->getRequestUri());
    }

    /**
     * @param-out array<string, string[]> $csp
     *
     * @throws \Exception
     *
     * @psalm-suppress ReferenceConstraintViolation
     */
    private function replaceNonce(array &$csp): void
    {
        $nonce = $this->service->getCspNonce();
        \array_walk_recursive(
            $csp,
            function (string &$value, string $key, string $nonce): void {
                if (self::CSP_NONCE_PARAM === $value) {
                    $value = $nonce;
                }
            },
            $nonce
        );
    }

    private function updateResponse(ResponseEvent $event, string $name): true
    {
        $finder = new Finder();
        $finder->in($this->publicDir . '/css')
            ->in($this->publicDir . '/vendor')
            ->name($name)
            ->files();

        foreach ($finder as $file) {
            try {
                $content = $file->getContents();
                $response = new Response($content);
                $event->setResponse($response);
            } catch (\RuntimeException) {
                // ignore
            }
            break;
        }

        return true;
    }
}
