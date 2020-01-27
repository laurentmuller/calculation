<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Pdf\PdfResponse;
use App\Twig\NonceExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Response subscriber to add content security policy (CSP).
 *
 * @author Laurent Muller
 */
class ResponseSubscriber implements EventSubscriberInterface
{
    /**
     * The CDN content delivery URL.
     */
    private const CDNJS_URL = 'https://cdnjs.cloudflare.com';

    /**
     * The data CSP directive.
     */
    private const CSP_DATA = 'data:';

    /**
     * The none CSP directive.
     */
    private const CSP_NONE = "'none'";

    /**
     * The self CSP directive.
     */
    private const CSP_SELF = "'self'";

    /**
     * The unsafe inline CSP directive.
     */
    private const CSP_UNSAFE_INLINE = "'unsafe-inline'";

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
     * The PDF plugin type.
     */
    private const PDF_TYPE = 'application/pdf';

    /**
     * The asset URL.
     *
     * @var string
     */
    private $asset;

    /**
     * The debug mode.
     *
     * @var bool
     */
    private $debug;

    /**
     * The nonce extension.
     *
     * @var NonceExtension
     */
    private $extension;

    /**
     * The reporting URL.
     *
     * @var string
     */
    private $reportUrl;

    /**
     * Constructor.
     *
     * @param KernelInterface    $kernel    the kernel to get the debug mode
     * @param RouterInterface    $router    the router to get reporting URL
     * @param ContainerInterface $container the container to get asset parameter
     * @param NonceExtension     $extension the extension to generate nonce
     */
    public function __construct(KernelInterface $kernel, RouterInterface $router, ContainerInterface $container, NonceExtension $extension)
    {
        $this->debug = $kernel->isDebug();
        $this->reportUrl = $router->generate('log_csp');
        $this->asset = $container->getParameter('asset_base');
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'updateResponse',
        ];
    }

    /**
     * Handle kernel response event.
     */
    public function updateResponse(ResponseEvent $event): void
    {
        // master request?
        if (!$event->isMasterRequest()) {
            return;
        }

        // Edge browser?
        $request = $event->getRequest();
        if ($this->isEdgeBrowser($request)) {
            return;
        }

        // developement firewall?
        if ($this->debug && $this->isDevFirewall($request)) {
            return;
        }

        // get response and headers
        $response = $event->getResponse();
        $headers = $response->headers;

        // CSP
        $csp = $this->getCSP($request, $response);
        $headers->set('Content-Security-Policy', $csp);
        $headers->set('X-Content-Security-Policy', $csp);
        $headers->set('X-WebKit-CSP', $csp);

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
     * @param Request  $request  the current request object
     * @param Response $response the current response object
     *
     * @return string the CSP directives
     */
    private function getCSP(Request $request, Response $response): string
    {
        // get values
        $asset = $this->asset;
        $nonce = $this->getNonce();

        // default
        $csp = [];

        // none
        $csp['default-src'] = self::CSP_NONE;
        $csp['object-src'] = self::CSP_NONE;
        $csp['media-src'] = self::CSP_NONE;
        $csp['base-uri'] = self::CSP_NONE;

        // self
        $csp['frame-ancestors'] = self::CSP_SELF;
        $csp['manifest-src'] = self::CSP_SELF;
        $csp['form-action'] = self::CSP_SELF;

        // nonce
        $csp['script-src'] = $nonce;

        // self + asset
        $csp['connect-src'] = [self::CSP_SELF, $asset];
        $csp['img-src'] = [self::CSP_SELF, self::CSP_DATA, $asset];
        $csp['frame-src'] = [self::CSP_SELF, self::GOOGLE_FRAME_URL];
        $csp['font-src'] = [self::CSP_SELF, self::GOOGLE_FONT_STATIC_URL, $asset];
        $csp['style-src'] = [self::CSP_SELF, self::GOOGLE_FONT_API_URL, self::CSP_UNSAFE_INLINE, $asset];

        // PDF response
        if ($response instanceof PdfResponse) {
            $csp['object-src'] = self::CSP_SELF;
            $csp['plugin-types'] = self::PDF_TYPE;
        }

        // reporting
        // see: https://mathiasbynens.be/notes/csp-reports
        // if ($this->debug) {
        //$csp['report-uri'] = $this->reportUrl;
        // }

        // build
        $result = ''; // "block-all-mixed-content;";
        foreach ($csp as $key => $entries) {
            $value = \is_array($entries) ? \implode(' ', $entries) : $entries;
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
        if ($context = $request->attributes->get('_firewall_context')) {
            $names = \explode('.', $context);

            return 'dev' === \end($names);
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

        return $agent && false !== \stripos($agent, 'edge');
    }
}
