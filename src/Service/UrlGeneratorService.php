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

namespace App\Service;

use App\Controller\IndexController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service to generate URL and parameters.
 *
 * @author Laurent Muller
 */
class UrlGeneratorService
{
    /**
     * The parameter names.
     *
     * @var string[]
     */
    public const PARAMETER_NAMES = [
        'id',
        // 'selection',
        'query',
        'page',
        'pagelength',
        'ordercolumn',
        'orderdir',
        'search',
        'caller',
    ];

    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface $generator the URL generator
     */
    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Generate the cancel URL.
     *
     * @param Request $request      the request
     * @param int     $id           the entity identifier
     * @param string  $defaultRoute the default route to use
     *
     * @return string the cancel URL
     */
    public function cancelUrl(Request $request, int $id = 0, string $defaultRoute = IndexController::HOME_PAGE): string
    {
        // build parameters
        $params = $this->routeParams($request, $id);

        // caller?
        if (isset($params['caller'])) {
            $caller = $params['caller'];
            unset($params['caller']);

            $caller .= (false === \strpos($caller, '?')) ? '?' : '&';
            $caller .= \http_build_query($params);

            return $caller;
        }

        // default route
        return $this->generator->generate($defaultRoute, $params);
    }

    /**
     * Generate the cancel URL and returns a redirect response.
     *
     * @param Request $request      the request
     * @param int     $id           the entity identifier
     * @param string  $defaultRoute the default route to use
     */
    public function redirect(Request $request, int $id = 0, string $defaultRoute = IndexController::HOME_PAGE): RedirectResponse
    {
        $url = $this->cancelUrl($request, $id, $defaultRoute);

        return new RedirectResponse($url);
    }

    /**
     * Gets a route parameters.
     *
     * @param Request $request the request
     * @param int     $id      the entity identifier
     *
     * @return array the parameters
     */
    public function routeParams(Request $request, int $id = 0): array
    {
        $params = [];

        // parameters
        foreach (self::PARAMETER_NAMES as $name) {
            if (null !== ($value = $request->get($name))) {
                $params[$name] = $value;
            }
        }

        // identifier
        if (0 !== $id) {
            $params['id'] = $id;
            //$params['selection'] = $id;
        }

        return $params;
    }
}
