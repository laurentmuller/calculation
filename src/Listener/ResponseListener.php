<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Interfaces\IResponseInterface;
use App\Twig\NonceExtension;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Response subscriber to add content security policy (CSP).
 *
 * @author Laurent Muller
 */
class ResponseListener implements EventSubscriberInterface
{
    /**
     * The CSP data directive.
     */
    private const CSP_DATA = 'data:';

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
     * The currency flag image url.
     */
    private const CURRENCY_FLAG_URL = 'https://wise.com';

    /**
     * The Google API font url.
     */
    private const GOOGLE_FONT_API_URL = 'https://fonts.googleapis.com';

    /**
     * The Google GStatic font url.
     */
    private const GOOGLE_FONT_STATIC_URL = 'https://fonts.gstatic.com';

    /**
     * The Google frame url.
     */
    private const GOOGLE_FRAME_URL = 'https://www.google.com';

    /**
     * The Iconify icons url.
     */
    private const ICONIFY_URL = 'https://api.iconify.design';

    /**
     * The Open weather image url.
     */
    private const OPEN_WEATHER_URL = 'https://openweathermap.org';

    /**
     * The Robohash image url (used for user avatar).
     */
    private const ROBOHASH_URL = 'https://robohash.org';

    /**
     * The asset URL.
     */
    private string $asset;

    /**
     * The reporting URL.
     */
    private string $reportUrl;

    /**
     * Constructor.
     */
    public function __construct(RouterInterface $router, ParameterBagInterface $params, private NonceExtension $extension, private bool $isDebug)
    {
        /** @var string $asset */
        $asset = $params->get('asset_base');

        $this->reportUrl = $router->generate('log_csp');
        $this->asset = $asset;
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
        // master request?
        if (!$event->isMainRequest()) {
            return;
        }

        // get values
        $request = $event->getRequest();
        $response = $event->getResponse();
        $headers = $response->headers;

        // developement firewall?
        if ($this->isDebug && $this->isDevFirewall($request)) {
            return;
        }

        // CSP
        if (!$this->isEdgeBrowser($request)) {
            $csp = $this->getCSP($response);
            $headers->set('Content-Security-Policy', $csp);
            $headers->set('X-Content-Security-Policy', $csp);
            $headers->set('X-WebKit-CSP', $csp);
        }

        // see: Dareboost
        $headers->set('X-FRAME-OPTIONS', 'SAMEORIGIN');
        $headers->set('X-XSS-Protection', '1; mode=block');
        $headers->set('X-Content-Type-Options', 'nosniff');

        // see: https://securityheaders.com/
        // see: https://github.com/aidantwoods/SecureHeaders
        $headers->set('referrer-policy', 'same-origin');
        $headers->set('x-permitted-cross-domain-policies', 'none');
    }

    /**
     * Build the content security policy.
     *
     * @param Response $response the current response object
     *
     * @return string the CSP directives
     */
    private function getCSP(Response $response): string
    {
        // get values
        $asset = $this->asset;
        $nonce = $this->getNonce();

        $csp = [
            // none
            'base-uri' => self::CSP_NONE,
            'media-src' => self::CSP_NONE,
            'object-src' => self::CSP_NONE,

            // self
            'default-src' => self::CSP_SELF,
            'form-action' => self::CSP_SELF,
            'manifest-src' => self::CSP_SELF,
            'frame-ancestors' => self::CSP_SELF,

            // nonce + asset
            'script-src' => [$nonce],
            'script-src-elem' => [$nonce, $asset, self::CSP_UNSAFE_INLINE, self::ICONIFY_URL],

            // self + asset
            'connect-src' => [self::CSP_SELF, $asset],
            'frame-src' => [self::CSP_SELF, self::GOOGLE_FRAME_URL],
            'font-src' => [self::CSP_SELF, self::GOOGLE_FONT_STATIC_URL, $asset],
            'style-src' => [self::CSP_SELF, self::GOOGLE_FONT_API_URL, self::CSP_UNSAFE_INLINE, $asset],
            'style-src-elem' => [self::CSP_SELF, self::GOOGLE_FONT_API_URL, self::CSP_UNSAFE_INLINE, $asset],
            'img-src' => [self::CSP_SELF, self::CSP_DATA, self::OPEN_WEATHER_URL, self::ROBOHASH_URL, self::CURRENCY_FLAG_URL, $asset],

            // reporting. see: https://mathiasbynens.be/notes/csp-reports
            'report-uri' => $this->reportUrl,
        ];

        // response interface?
        if ($response instanceof IResponseInterface) {
            $csp['object-src'] = self::CSP_SELF;
            $csp['plugin-types'] = $response->getMimeType();
        }

        // build
        $result = '';
        foreach ($csp as $key => $entries) {
            $value = \implode(' ', (array) $entries);
            $result .= "{$key} {$value};";
        }

        return $result;
    }

    /**
     * Gets the CSP nonce directive.
     */
    private function getNonce(): string
    {
        $nonce = $this->extension->getNonce();

        return "'nonce-{$nonce}'";
    }

    /**
     * Returns if the current firewall is the developement.
     *
     * @param Request $request the current request object
     *
     * @return bool true if developement firewall
     */
    private function isDevFirewall(Request $request): bool
    {
        /** @psalm-var mixed $context */
        $context = $request->attributes->get('_firewall_context');
        if (\is_string($context)) {
            return false !== \stripos($context, 'dev');
        }

        return false;
    }

    /**
     * Returns if the browser is Edge.
     *
     * @param Request $request the current request object
     *
     * @return bool true if the browser is Edge
     */
    private function isEdgeBrowser(Request $request): bool
    {
        $agent = $request->headers->get('user-agent');

        return null !== $agent && false !== \stripos($agent, 'edge');
    }
}
