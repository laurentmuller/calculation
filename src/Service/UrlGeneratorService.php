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

namespace App\Service;

use App\Controller\AbstractController;
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
     */
    private const PARAMETER_NAMES = [
        'id',
        'caller',

        'query', // datatables
        'page',
        'pagelength',
        'ordercolumn',
        'orderdir',

        'search', // bootstrap-table
        'sort',
        'order',
        'offset',
        'limit',
        'view',

        'groupId', // bootstrap-table group
        'categoryId', // bootstrap-table product
        'stateId', // bootstrap-table calculation
        'channel', // bootstrap-table log
        'level',
        'entity', // bootstrap-table search

        'type', // seach page
    ];

    private UrlGeneratorInterface $generator;

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
    public function cancelUrl(Request $request, int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): string
    {
        // build parameters
        $params = $this->routeParams($request, $id);

        // caller?
        if (isset($params['caller']) && !empty($params['caller'])) {
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
    public function redirect(Request $request, int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): RedirectResponse
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
        }

        return $params;
    }
}
