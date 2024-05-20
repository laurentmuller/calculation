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
 * Value resolver for DataQuery.
 */
final readonly class DataQueryValueResolver implements SortModeInterface, ValueResolverInterface
{
    use CookieTrait;

    public function __construct(private PropertyAccessorInterface $accessor)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        if (DataQuery::class !== $type) {
            return [];
        }

        /** @psalm-var DataQuery $query */
        $query = $argument->getDefaultValue();
        $this->updateFromQuery($query, $request);
        $this->updateFromRequest($query, $request);

        return [$query];
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

    private function getView(Request $request): TableView
    {
        return $this->getCookieEnum($request, TableInterface::PARAM_VIEW, TableView::TABLE);
    }

    private function isCallback(Request $request): bool
    {
        return $request->isXmlHttpRequest();
    }

    private function updateFromQuery(DataQuery $query, Request $request): void
    {
        $data = $request->query;
        /** @psalm-var string $key */
        foreach ($data->keys() as $key) {
            $value = match ($key) {
                TableInterface::PARAM_VIEW => $data->getEnum($key, TableView::class, $query->view),
                default => $data->get($key)
            };
            $this->accessor->setValue($query, $key, $value);
        }
    }

    private function updateFromRequest(DataQuery $query, Request $request): void
    {
        $query->view = $this->getView($request);
        $query->prefix = $this->getPrefix($request);
        $query->callback = $this->isCallback($request);
        if (0 === $query->limit) {
            $query->limit = $this->getLimit($request, $query->prefix, $query->view);
        }
        if ('' === $query->sort) {
            $query->sort = $this->getSort($request, $query->prefix);
            $query->order = $this->getOrder($request, $query->prefix);
        }
    }
}
