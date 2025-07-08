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

namespace App\Table;

use App\Repository\CalculationRepository;
use App\Utils\FileUtils;

/**
 * Abstract Calculation table to display items.
 *
 * @phpstan-import-type CalculationItemType from CalculationRepository
 * @phpstan-import-type CalculationItemEntry from CalculationRepository
 */
abstract class AbstractCalculationItemsTable extends AbstractTable implements \Countable
{
    public function __construct(protected readonly CalculationRepository $repository)
    {
    }

    #[\Override]
    public function getEntityClassName(): ?string
    {
        return $this->repository->getClassName();
    }

    /**
     * Gets the repository.
     */
    public function getRepository(): CalculationRepository
    {
        return $this->repository;
    }

    /**
     * Formats the invalid calculation items.
     *
     * @param array $items the invalid calculation items
     *
     * @phpstan-param CalculationItemEntry[] $items
     *
     * @return string the formatted items
     */
    abstract protected function formatItems(array $items): string;

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'calculation_items.json');
    }

    /**
     * Gets the entities.
     *
     * @param string $orderColumn    the order column
     * @param string $orderDirection the order direction ('ASC' or 'DESC')
     *
     * @phpstan-param self::SORT_* $orderDirection
     *
     * @phpstan-return CalculationItemType[]
     */
    abstract protected function getEntities(string $orderColumn = 'id', string $orderDirection = self::SORT_DESC): array;

    /**
     * Compute the number of calculation items.
     *
     * @param array $items the invalid calculation items
     *
     * @phpstan-param CalculationItemType[] $items
     */
    abstract protected function getItemsCount(array $items): int;

    #[\Override]
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);
        $entities = $this->getEntities($query->sort, $query->order);
        $results->totalNotFiltered = $results->filtered = \count($entities);
        $entities = \array_slice($entities, $query->offset, $query->limit);
        $results->rows = $this->mapEntities($entities);
        if (!$query->callback) {
            $results->customData = [
                'itemsCount' => $this->getItemsCount($entities),
                'allow_search' => false,
            ];
        }

        return $results;
    }

    #[\Override]
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
        }
    }
}
