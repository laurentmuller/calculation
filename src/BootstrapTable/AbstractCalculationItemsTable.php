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
use App\Interfaces\TableInterface;
use App\Repository\CalculationRepository;
use Doctrine\Common\Collections\Criteria;

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
     * Gets the number of calculations.
     */
    abstract public function countEntities(): int;

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
     * Returns a value indicating if no items match.
     *
     * @return bool true if empty
     */
    public function isEmpty(): bool
    {
        return 0 === $this->countEntities();
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
    abstract protected function getEntities(string $orderColumn = 'id', string $orderDirection = Criteria::DESC): array;

    /**
     * Compute the number of calculation items.
     *
     * @param array $items the calculations
     *
     * @return int the number of calculation items
     */
    abstract protected function getItemsCount(array $items): int;

    /**
     * {@inheritDoc}
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = new DataResults();

        // find all
        $entities = $this->getEntities($query->sort, $query->order);
        $results->totalNotFiltered = $results->filtered = \count($entities);

        // limit
        $entities = \array_slice($entities, $query->offset, $query->limit);

        // map
        $results->rows = $this->mapEntities($entities);

        // ajax?
        if ($query->callback) {
            return $results;
        }

        // page list
        $pageList = $this->getAllowedPageList($results->totalNotFiltered);
        $limit = \min($query->limit, \max($pageList));

        // results
        $results->columns = $this->getColumns();
        $results->pageList = $pageList;
        $results->limit = $limit;

        // action parameters
        $results->params = [
            TableInterface::PARAM_ID => $query->id,
            TableInterface::PARAM_SEARCH => $query->search,
            TableInterface::PARAM_SORT => $query->sort,
            TableInterface::PARAM_ORDER => $query->order,
            TableInterface::PARAM_OFFSET => $query->offset,
            TableInterface::PARAM_LIMIT => $limit,
            TableInterface::PARAM_CARD => $query->card,
        ];

        // custom data
        $results->customData = [
            'itemsCount' => $this->getItemsCount($entities),
            'allow_search' => false,
        ];

        // table attributes
        $results->attributes = [
            'total-not-filtered' => $results->totalNotFiltered,
            'total-rows' => $results->filtered,

            'search' => \json_encode(false),
            'search-text' => $query->search,

            'page-list' => $this->implodePageList($pageList),
            'page-size' => $limit,
            'page-number' => $query->page,

            'card-view' => \json_encode($query->card),

            'sort-name' => $query->sort,
            'sort-order' => $query->order,
        ];

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        $results->addAttribute('row-style', 'styleCalculationEditable');
    }
}
