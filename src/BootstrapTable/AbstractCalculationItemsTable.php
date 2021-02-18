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

namespace App\BootstrapTable;

use App\Interfaces\EntityVoterInterface;
use App\Repository\CalculationRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Abstract Calculation table to display items.
 *
 * @author Laurent Muller
 */
abstract class AbstractCalculationItemsTable extends AbstractTable
{
    /**
     * The repository to get entities.
     *
     * @var CalculationRepository
     */
    protected $repository;

    /**
     * The entities.
     *
     * @var array
     */
    private $entities;
    /**
     * The number of items.
     *
     * @var int
     */
    private $itemsCount = 0;

    /**
     * Constructor.
     */
    public function __construct(CalculationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Formats the invalid calculation items.
     *
     * @param array $items the invalid calculation items
     *
     * @return string the formatted items
     */
    abstract public function formatItems(array $items): string;

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): ?string
    {
        return EntityVoterInterface::ENTITY_CALCULATION;
    }

    /**
     * Gets the number of empty items.
     */
    public function getItemCounts(): int
    {
        return $this->itemsCount;
    }

    /**
     * Gets the repository.
     */
    public function getRepository(): CalculationRepository
    {
        return $this->repository;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Request $request): array
    {
        // sort
        [$sort, $order] = $this->getSort($request);

        // find all
        $entities = $this->entities ?? $this->getEntities($sort, $order);
        $totalNotFiltered = $filtered = \count($entities);

        // filter search
        if ($search = (string) $request->get(self::PARAM_SEARCH, '')) {
        }

        // limit
        [$offset, $limit, $page] = $this->getLimit($request);
        $entities = \array_slice($entities, $offset, $limit);

        // map
        $rows = $this->mapEntities($entities);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return [
                self::PARAM_TOTAL_NOT_FILTERED => $totalNotFiltered,
                self::PARAM_TOTAL => $filtered,
                self::PARAM_ROWS => $rows,
            ];
        }

        // page list
        $pageList = $this->getAllowedPageList($totalNotFiltered);
        $limit = \min($limit, \max($pageList));

        // card view
        $card = $this->getParamCard($request);

        // render
        return [
            // template parameters
            self::PARAM_COLUMNS => $this->getColumns(),
            self::PARAM_ROWS => $rows,
            self::PARAM_PAGE_LIST => $pageList,
            self::PARAM_LIMIT => $limit,

            // custom parameters
            'itemsCount' => $this->getItemsCount($entities),
            'allow_search' => false,

            // action parameters
            'params' => [
                self::PARAM_ID => $this->getParamId($request),
                self::PARAM_SEARCH => $search,
                self::PARAM_SORT => $sort,
                self::PARAM_ORDER => $order,
                self::PARAM_OFFSET => $offset,
                self::PARAM_LIMIT => $limit,
                self::PARAM_CARD => $card,
            ],

            // table attributes
            'attributes' => [
                'total-not-filtered' => $totalNotFiltered,
                'total-rows' => $filtered,

                'search' => \json_encode(false),
                'search-text' => $search,

                'page-list' => $this->implodePageList($pageList),
                'page-size' => $limit,
                'page-number' => $page,

                'card-view' => \json_encode($this->getParamCard($request)),

                'sort-name' => $sort,
                'sort-order' => $order,
            ],
        ];
    }

    /**
     * Returns a value indicating if no items match.
     *
     * @return bool true if empty
     */
    public function isEmpty(Request $request): bool
    {
        [$sort, $order] = $this->getSort($request);
        $this->entities = $this->getEntities($sort, $order);

        return empty($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/calculation_items.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['id' => Column::SORT_DESC];
    }

    /**
     * Gets the invalid items.
     *
     * @param string $orderColumn    the order column
     * @param string $orderDirection the order direction ('ASC' or 'DESC')
     */
    abstract protected function getEntities(string $orderColumn, string $orderDirection): array;

    /**
     * Compute the number of calculation items.
     *
     * @param array $items the calculations
     *
     * @return int the number of calculation items
     */
    abstract protected function getItemsCount(array $items): int;

    /**
     * Gets the limit, the maximum and the page parameters.
     *
     * @param Request $request the request to get values from
     *
     * @return int[] the offset, the limit and the page parameters
     */
    private function getLimit(Request $request): array
    {
        $offset = (int) $request->get(self::PARAM_OFFSET, 0);
        $limit = (int) $this->getRequestValue($request, self::PARAM_LIMIT, self::PAGE_SIZE);
        $page = 1 + (int) \floor($this->safeDivide($offset, $limit));

        return [$offset, $limit, $page];
    }

    /**
     * Gets the sorted field and order.
     *
     * @param Request $request the request to get values from
     *
     * @return string[] the sorted field and order
     */
    private function getSort(Request $request): array
    {
        $sort = (string) $this->getRequestValue($request, self::PARAM_SORT, 'id');
        $order = (string) $this->getRequestValue($request, self::PARAM_ORDER, Column::SORT_DESC);

        return [$sort, $order];
    }
}
