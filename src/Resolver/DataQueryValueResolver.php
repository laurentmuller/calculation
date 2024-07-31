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

namespace App\Resolver;

use App\Enums\TableView;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Table\DataQuery;
use App\Traits\CookieTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Value resolver for {@link DataQuery}.
 */
final readonly class DataQueryValueResolver implements SortModeInterface, ValueResolverInterface
{
    use CookieTrait;

    public function __construct(private PropertyAccessorInterface $accessor)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (DataQuery::class !== $argument->getType()) {
            return [];
        }

        $query = $this->createQuery($request);

        return [$query];
    }

    private function createQuery(Request $request): DataQuery
    {
        $query = new DataQuery();

        /** @psalm-var string|int $value */
        foreach ($request->query as $key => $value) {
            // special case for view
            if (TableInterface::PARAM_VIEW === $key) {
                $value = TableView::tryFrom((string) $value) ?? $query->view;
            }
            $this->accessor->setValue($query, $key, $value);
        }

        $query->prefix = $this->getPrefix($request);
        $query->callback = $this->isCallback($request);
        $query->view = $this->getView($request, $query->view);
        if (0 === $query->limit) {
            $query->limit = $this->getLimit($request, $query->prefix, $query->view);
        }
        if ('' === $query->sort) {
            $query->sort = $this->getSort($request, $query->prefix);
            $query->order = $this->getOrder($request, $query->prefix);
        }

        return $query;
    }

    private function getLimit(Request $request, string $prefix, TableView $view): int
    {
        return $this->getCookieInt($request, TableInterface::PARAM_LIMIT, $prefix, $view->getPageSize());
    }

    /**
     * @psalm-return self::SORT_*
     */
    private function getOrder(Request $request, string $prefix): string
    {
        /** @psalm-var self::SORT_* */
        return $this->getCookieString($request, TableInterface::PARAM_ORDER, $prefix, self::SORT_ASC);
    }

    private function getPrefix(Request $request): string
    {
        return \strtoupper($request->attributes->getString('_route'));
    }

    private function getSort(Request $request, string $prefix): string
    {
        return $this->getCookieString($request, TableInterface::PARAM_SORT, $prefix);
    }

    private function getView(Request $request, TableView $default): TableView
    {
        return $this->getCookieEnum($request, TableInterface::PARAM_VIEW, $default);
    }

    private function isCallback(Request $request): bool
    {
        return $request->isXmlHttpRequest();
    }
}
