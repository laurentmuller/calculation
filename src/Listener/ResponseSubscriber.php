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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
     * The CSP blob directive.
     */
    private const CSP_BLOB = 'blob:';

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
     * @param KernelInterface       $kernel    the kernel to get the debug mode
     * @param RouterInterface       $router    the router to get reporting URL
     * @param ParameterBagInterface $params    the bag to get asset parameter
     * @param NonceExtension        $extension the extension to generate nonce
     */
    public function __construct(KernelInterface $kernel, RouterInterface $router, ParameterBagInterface $params, NonceExtension $extension)
    {
        $this->debug = $kernel->isDebug();
        $this->reportUrl = $router->generate('log_csp');
        $this->asset = $params->get('asset_base');
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
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

        // get values
        $request = $event->getRequest();
        $response = $event->getResponse();
        $headers = $response->headers;

        // developement firewall?
        if ($this->debug && $this->isDevFirewall($request)) {
            return;
        }

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

        // none
        $csp['base-uri'] = self::CSP_NONE;
        $csp['media-src'] = self::CSP_NONE;
        $csp['object-src'] = self::CSP_NONE;

        // self
        $csp['default-src'] = self::CSP_SELF;
        $csp['form-action'] = self::CSP_SELF;
        $csp['frame-ancestors'] = self::CSP_SELF;
        if (!$this->isEdgeBrowser($request)) {
            $csp['manifest-src'] = self::CSP_SELF;
        }

        // nonce + asset
        $csp['script-src'] = [$nonce]; //, $asset];
        $csp['script-src-elem'] = [$nonce, $asset, self::CSP_UNSAFE_INLINE];

        // self + asset
        $csp['frame-src'] = [self::CSP_SELF, self::GOOGLE_FRAME_URL];
        $csp['connect-src'] = [self::CSP_SELF, $asset]; //, self::CSP_BLOB
        $csp['font-src'] = [self::CSP_SELF, self::GOOGLE_FONT_STATIC_URL, $asset];
        $csp['img-src'] = [self::CSP_SELF, self::CSP_DATA, $asset]; //, self::CSP_BLOB
        $csp['style-src'] = [self::CSP_SELF, self::GOOGLE_FONT_API_URL, self::CSP_UNSAFE_INLINE, $asset];

        // PDF response
        if ($response instanceof PdfResponse) {
            $csp['object-src'] = self::CSP_SELF;
            $csp['plugin-types'] = self::PDF_TYPE;
        }

        // reporting
        // see: https://mathiasbynens.be/notes/csp-reports
        if ($this->debug) {
            $csp['report-uri'] = $this->reportUrl;
        }

        // build
        $result = '';
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

        return $agent && false !== \stripos($agent, 'edge');
    }
}
