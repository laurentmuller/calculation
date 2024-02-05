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

namespace App\Service;

use App\Controller\AbstractController;
use App\Controller\IndexController;
use App\Interfaces\EntityInterface;
use App\Interfaces\TableInterface;
use App\Table\AbstractCategoryItemTable;
use App\Table\CalculationTable;
use App\Table\CategoryTable;
use App\Table\LogTable;
use App\Table\SearchTable;
use App\Traits\RequestTrait;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service to generate URL and parameters.
 */
class UrlGeneratorService
{
    use RequestTrait;

    /**
     * The caller parameter name.
     */
    final public const PARAM_CALLER = 'caller';

    /**
     * The parameter names.
     */
    private const PARAMETER_NAMES = [
        // global
        self::PARAM_CALLER,

        // index page
        IndexController::PARAM_CUSTOM,
        IndexController::PARAM_RESTRICT,

        // bootstrap-table
        TableInterface::PARAM_ID,
        TableInterface::PARAM_SEARCH,
        TableInterface::PARAM_SORT,
        TableInterface::PARAM_ORDER,
        TableInterface::PARAM_OFFSET,
        TableInterface::PARAM_VIEW,
        TableInterface::PARAM_LIMIT,

        // tables
        LogTable::PARAM_LEVEL,
        LogTable::PARAM_CHANNEL,

        CategoryTable::PARAM_GROUP,
        CalculationTable::PARAM_STATE,
        CalculationTable::PARAM_EDITABLE,
        AbstractCategoryItemTable::PARAM_CATEGORY,

        SearchTable::PARAM_TYPE,
        SearchTable::PARAM_ENTITY,
    ];

    public function __construct(private readonly UrlGeneratorInterface $generator)
    {
    }

    /**
     * Generate the cancel URL.
     */
    public function cancelUrl(
        Request $request,
        EntityInterface|int|null $id = 0,
        string $defaultRoute = AbstractController::HOME_PAGE,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $params = $this->routeParams($request, $id);
        $caller = $this->getCaller($params);
        if (null !== $caller) {
            unset($params[self::PARAM_CALLER]);
            if ([] !== $params) {
                $caller .= \str_contains($caller, '?') ? '&' : '?';
                $caller .= \http_build_query($params);
            }

            return $caller;
        }

        return $this->generator->generate($defaultRoute, $params, $referenceType);
    }

    /**
     * Generate the cancel URL and returns a redirect response.
     */
    public function redirect(
        Request $request,
        EntityInterface|int|null $id = 0,
        string $defaultRoute = AbstractController::HOME_PAGE,
        int $status = Response::HTTP_FOUND,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): RedirectResponse {
        $url = $this->cancelUrl($request, $id, $defaultRoute, $referenceType);

        return new RedirectResponse($url, $status);
    }

    /**
     * Gets the request parameters.
     *
     * @psalm-return array<string, string|int|float|bool>
     */
    public function routeParams(Request $request, EntityInterface|int|null $id = 0): array
    {
        $params = [];
        foreach (self::PARAMETER_NAMES as $name) {
            $value = $this->getRequestValue($request, $name);
            if (null !== $value) {
                $params[$name] = $value;
            }
        }
        if ($id instanceof EntityInterface) {
            $id = $id->getId();
        }
        if (null !== $id && 0 !== $id) {
            $params['id'] = $id;
        }

        return $params;
    }

    /**
     * Gets the caller parameter.
     *
     * @param array<string, string|int|float|bool> $params
     */
    private function getCaller(array $params): ?string
    {
        /** @psalm-var string|null $caller */
        $caller = $params[self::PARAM_CALLER] ?? null;
        if (StringUtils::isString($caller)) {
            return ('/' === $caller) ? $caller : \rtrim($caller, '/');
        }

        return null;
    }
}
