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

use App\BootstrapTable\AbstractCategoryItemTable;
use App\BootstrapTable\CalculationTable;
use App\BootstrapTable\CategoryTable;
use App\BootstrapTable\LogTable;
use App\BootstrapTable\SearchTable;
use App\Controller\AbstractController;
use App\DataTable\Model\AbstractDataTable;
use App\Interfaces\TableInterface;
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
     * The caller parameter name.
     */
    public const PARAM_CALLER = 'caller';

    /**
     * The parameter names.
     */
    private const PARAMETER_NAMES = [
        // global
        self::PARAM_CALLER,

        // datatables
        AbstractDataTable::PARAM_QUERY,
        AbstractDataTable::PARAM_PAGE_INDEX,
        AbstractDataTable::PARAM_PAGE_LENGTH,
        AbstractDataTable::PARAM_ORDER_COLUMN,
        AbstractDataTable::PARAM_ORDER_DIR,

        // bootstrap-table
        TableInterface::PARAM_ID,
        TableInterface::PARAM_SEARCH,
        TableInterface::PARAM_SORT,
        TableInterface::PARAM_ORDER,
        TableInterface::PARAM_OFFSET,
        TableInterface::PARAM_VIEW,
        TableInterface::PARAM_LIMIT,

        // entities
        LogTable::PARAM_LEVEL,
        LogTable::PARAM_CHANNEL,

        CategoryTable::PARAM_GROUP,
        CalculationTable::PARAM_STATE,
        AbstractCategoryItemTable::PARAM_CATEGORY,

        SearchTable::PARAM_TYPE,
        SearchTable::PARAM_ENTITY,
    ];

    private UrlGeneratorInterface $generator;

    /**
     * Constructor.
     */
    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Generate the cancel URL.
     */
    public function cancelUrl(Request $request, ?int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): string
    {
        // build parameters
        $params = $this->routeParams($request, $id);

        // caller?
        if (null !== $caller = $this->getCaller($params)) {
            if (!empty($params)) {
                $caller .= (false === \strpos($caller, '?')) ? '?' : '&';
                $caller .= \http_build_query($params);
            }

            return $caller;
        }

        // default route
        return $this->generator->generate($defaultRoute, $params);
    }

    /**
     * Generate the cancel URL and returns a redirect response.
     */
    public function redirect(Request $request, ?int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): RedirectResponse
    {
        $url = $this->cancelUrl($request, $id, $defaultRoute);

        return new RedirectResponse($url);
    }

    /**
     * Gets the request parameters.
     */
    public function routeParams(Request $request, ?int $id = 0): array
    {
        $params = [];

        // parameters
        foreach (self::PARAMETER_NAMES as $name) {
            if (null !== ($value = $request->get($name))) {
                $params[$name] = $value;
            }
        }

        // identifier
        if (!empty($id)) {
            $params['id'] = $id;
        }

        return $params;
    }

    /**
     * Gets the caller parameter.
     */
    private function getCaller(array &$params): ?string
    {
        $caller = $params[self::PARAM_CALLER] ?? null;
        if (!empty($caller)) {
            unset($params[self::PARAM_CALLER]);

            return \rtrim($caller, '/');
        }

        return null;
    }
}
