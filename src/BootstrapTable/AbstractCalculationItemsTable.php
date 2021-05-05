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
use Doctrine\Common\Collections\Criteria;

/**
 * Abstract Calculation table to display items.
 *
 * @author Laurent Muller
 */
abstract class AbstractCalculationItemsTable extends AbstractTable implements \Countable
{
    /**
     * The repository to get entities.
     */
    protected CalculationRepository $repository;

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
        return 0 === $this->count();
    }

    /**
     * {@inheritDoc}
     */
    public function isEmptyAllowed(): bool
    {
        return false;
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

        // limit and and map entities
        $entities = \array_slice($entities, $query->offset, $query->limit);
        $results->rows = $this->mapEntities($entities);

        // ajax?
        if (!$query->callback) {
            $results->customData = [
                'itemsCount' => $this->getItemsCount($entities),
                'allow_search' => false,
            ];
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
        }
    }
}
