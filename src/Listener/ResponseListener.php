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
use App\Twig\NonceExtension;
use App\Util\FileUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
class ResponseListener implements EventSubscriberInterface
{
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

    /*
     * The CSP directives.
     *
     * @var array<string, string[]>
     */
    private readonly array $csp;

    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct(ParameterBagInterface $params, UrlGeneratorInterface $router, NonceExtension $extension, string $file, private readonly bool $isDebug)
    {
        /** @var string $asset */
        $asset = $params->get('asset_base');
        $nonce = "'nonce-" . $extension->getNonce() . "'";
        $report = $router->generate('log_csp', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->csp = $this->loadCSP($file, $asset, $nonce, $report);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
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
        if ($this->isDebug && $this->isDevFirewall($request)) {
            return;
        }

        // CSP
        $response = $event->getResponse();
        $headers = $response->headers;
        if (!$this->isEdgeBrowser($request) && '' !== $csp = $this->getCSP($response)) {
            $headers->set('Content-Security-Policy', $csp);
            $headers->set('X-Content-Security-Policy', $csp);
            $headers->set('X-WebKit-CSP', $csp);
        }

        // see: https://www.dareboost.com
        $headers->set('X-FRAME-OPTIONS', 'sameorigin');
        $headers->set('X-XSS-Protection', '1; mode=block');
        $headers->set('X-Content-Type-Options', 'nosniff');

        // see: https://securityheaders.com/
        // see: https://github.com/aidantwoods/SecureHeaders
        $headers->set('referrer-policy', 'same-origin');
        $headers->set('x-permitted-cross-domain-policies', self::CSP_NONE);
    }

    /**
     * Build the content security policy.
     */
    private function getCSP(Response $response): string
    {
        /** @var array<string, string[]> $csp */
        $csp = $this->csp;
        if (empty($csp)) {
            return '';
        }

        // mime type?
        if ($response instanceof MimeTypeInterface) {
            $csp['object-src'] = [self::CSP_SELF];
            $csp['plugin-types'] = [$response->getMimeType()];
        }

        $keys = \array_keys($csp);

        return \array_reduce($keys, fn (string $carry, string $key): string => $carry . $key . ' ' . \implode(' ', $csp[$key]) . ';', '');
    }

    /**
     * Returns if the current firewall is the development.
     */
    private function isDevFirewall(Request $request): bool
    {
        /** @psalm-var mixed $context */
        $context = $request->attributes->get('_firewall_context');

        return \is_string($context) && false !== \stripos($context, 'dev');
    }

    /**
     * Returns if the browser is Edge.
     */
    private function isEdgeBrowser(Request $request): bool
    {
        $agent = $request->headers->get('user-agent');

        return null !== $agent && false !== \stripos($agent, 'edge');
    }

    /**
     * Load the CSP definition.
     *
     * @return array<string, string[]>
     */
    private function loadCSP(string $file, string $asset, string $nonce, string $report): array
    {
        if (!FileUtils::exists($file)) {
            return [];
        }
        if (false === $content = \file_get_contents($file)) {
            return [];
        }

        /** @var array<string, string|string[]> $csp */
        $csp = \json_decode($content, true);
        if (\JSON_ERROR_NONE !== \json_last_error() || empty($csp)) {
            return [];
        }

        // convert each entry to array
        $csp = \array_map(static fn (string|array $value): array => (array) $value, $csp);

        // replace
        $search = [
            'none',
            'self',
            'unsafe-inline',
            '%asset%',
            '%nonce%',
            '%report%',
        ];
        $replace = [
            self::CSP_NONE,
            self::CSP_SELF,
            self::CSP_UNSAFE_INLINE,
            $asset,
            $nonce,
            $report,
        ];

        /**
         * @psalm-var array<string, string[]> $result
         */
        $result = \array_map(fn (array $values): array => \str_replace($search, $replace, $values), $csp);

        return $result;
    }
}
