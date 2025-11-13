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
use App\Traits\ArrayTrait;
use App\Traits\RequestTrait;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Service to generate URL and parameters.
 */
class UrlGeneratorService
{
    use ArrayTrait;
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
        // log table
        LogTable::PARAM_LEVEL,
        LogTable::PARAM_CHANNEL,
        // entity tables
        CategoryTable::PARAM_GROUP,
        CalculationTable::PARAM_STATE,
        CalculationTable::PARAM_EDITABLE,
        AbstractCategoryItemTable::PARAM_CATEGORY,
        // search table
        SearchTable::PARAM_ENTITY,
    ];

    public function __construct(private readonly UrlGeneratorInterface $generator)
    {
    }

    /**
     * Generate the cancel URL.
     *
     * @param Request                  $request       the request to get parameters
     * @param EntityInterface|int|null $id            the entity identifier
     * @param string                   $routeName     the default route name
     * @param int                      $referenceType the reference type (absolute or relative)
     */
    #[AsTwigFunction(name: 'cancel_url')]
    public function cancelUrl(
        Request $request,
        EntityInterface|int|null $id = 0,
        string $routeName = AbstractController::HOME_PAGE,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $params = $this->routeParams($request, $id);
        $caller = $this->getCaller($params);
        if (null === $caller) {
            return $this->generate($routeName, $params, $referenceType);
        }
        unset($params[self::PARAM_CALLER]);
        if ([] === $params) {
            return $caller;
        }

        return $caller . '?' . \http_build_query($params);
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * @param string $routeName     the route name
     * @param array  $parameters    the parameters that reference placeholders in the route pattern
     * @param int    $referenceType the reference type (absolute or relative)
     */
    public function generate(
        string $routeName,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->generator->generate($routeName, $parameters, $referenceType);
    }

    /**
     * Generate the cancel URL and returns a redirect response.
     *
     * @param Request                  $request       the request to get parameters
     * @param EntityInterface|int|null $id            the entity identifier
     * @param string                   $routeName     the default route name
     * @param int                      $referenceType the reference type (absolute or relative)
     */
    public function redirect(
        Request $request,
        EntityInterface|int|null $id = 0,
        string $routeName = AbstractController::HOME_PAGE,
        int $status = Response::HTTP_FOUND,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): RedirectResponse {
        $url = $this->cancelUrl($request, $id, $routeName, $referenceType);

        return new RedirectResponse($url, $status);
    }

    /**
     * Gets the request parameters.
     *
     * @param Request                  $request the request to get parameters
     * @param EntityInterface|int|null $id      the entity identifier
     *
     * @return array<string, string|int|float|bool>
     */
    #[AsTwigFunction(name: 'route_params')]
    public function routeParams(Request $request, EntityInterface|int|null $id = 0): array
    {
        $params = $this->mapToKeyValue(
            self::PARAMETER_NAMES,
            fn (string $name): array => [$name => $this->getRequestValue($request, $name)]
        );
        $entityId = $this->getEntityId($id);
        if (0 !== $entityId) {
            $params[TableInterface::PARAM_ID] = $entityId;
        }

        return \array_filter($params);
    }

    /**
     * Gets the caller parameter.
     *
     * @param array<string, string|int|float|bool> $params
     */
    private function getCaller(array $params): ?string
    {
        /** @var ?string $caller */
        $caller = $params[self::PARAM_CALLER] ?? null;

        return StringUtils::trim($caller);
    }

    private function getEntityId(EntityInterface|int|null $id): int
    {
        return (int) ($id instanceof EntityInterface ? $id->getId() : $id);
    }
}
