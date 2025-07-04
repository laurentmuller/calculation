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

use App\Interfaces\EntityInterface;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Traits\ArrayTrait;
use App\Utils\FormatUtils;
use Symfony\Component\Clock\DatePoint;

/**
 * The abstract table.
 *
 * @phpstan-import-type EntityType from Column
 */
abstract class AbstractTable implements SortModeInterface
{
    use ArrayTrait;

    /**
     * The column definitions.
     *
     * @var Column[]|null
     */
    private ?array $columns = null;

    public function formatAmount(float $value): string
    {
        return FormatUtils::formatAmount($value);
    }

    public function formatDate(DatePoint $value): string
    {
        return FormatUtils::formatDate($value);
    }

    public function formatId(int $value): string
    {
        return FormatUtils::formatId($value);
    }

    public function formatInt(\Countable|array|int $value): string
    {
        return FormatUtils::formatInt($value);
    }

    public function formatPercent(float $value): string
    {
        return FormatUtils::formatPercent($value);
    }

    /**
     * Gets the column definitions.
     *
     * @return Column[]
     *
     * @phpstan-return array<int, Column>
     */
    public function getColumns(): array
    {
        if (null === $this->columns) {
            $this->columns = $this->createColumns();
        }

        /** @phpstan-var array<int, Column> */
        return $this->columns;
    }

    /**
     * Gets the translatable message to show when no data is available.
     */
    public function getEmptyMessage(): ?string
    {
        return null;
    }

    /**
     * Gets the entity class name or null if not applicable.
     */
    public function getEntityClassName(): ?string
    {
        return null;
    }

    /**
     * Process the given query and returns the results.
     */
    public function processDataQuery(DataQuery $query): DataResults
    {
        $this->updateDataQuery($query);
        $results = $this->handleQuery($query);
        $this->updateResults($query, $results);

        return $results;
    }

    /**
     * Gets the allowed page list.
     *
     * @param int $totalNotFiltered the number of not filtered entities
     *
     * @return int[] the allowed page list
     */
    protected function getAllowedPageList(int $totalNotFiltered): array
    {
        $sizes = TableInterface::PAGE_LIST;
        if (\end($sizes) <= $totalNotFiltered) {
            return $sizes;
        }

        foreach ($sizes as $index => $size) {
            if ($size >= $totalNotFiltered) {
                return \array_slice($sizes, 0, $index + 1);
            }
        }

        return $sizes;
    }

    /**
     * Gets the JSON file containing the column definitions.
     */
    abstract protected function getColumnDefinitions(): string;

    /**
     * Gets the default sorting column.
     */
    protected function getDefaultColumn(): ?Column
    {
        $columns = $this->getColumns();

        return $this->findFirst($columns, static fn (Column $column): bool => $column->isDefault())
            ?? $this->findFirst($columns, static fn (Column $column): bool => $column->isVisible());
    }

    /**
     * Handle the query parameters.
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        return new DataResults();
    }

    /**
     * Maps the given entities.
     *
     * @param EntityType[] $entities the entities to map
     *
     * @return array<array<string, string>> the mapped entities
     */
    protected function mapEntities(array $entities): array
    {
        if ([] === $entities) {
            return [];
        }

        $columns = $this->getColumns();

        return \array_map(fn (EntityInterface|array $entity): array => $this->mapEntity($entity, $columns), $entities);
    }

    /**
     * Update the data query.
     */
    protected function updateDataQuery(DataQuery $query): void
    {
        if ('' !== $query->sort) {
            return;
        }
        $column = $this->getDefaultColumn();
        if (!$column instanceof Column) {
            return;
        }
        $query->sort = $column->getField();
        $query->order = $column->getOrder();
    }

    /**
     * Update the results before sending back.
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        $results->pageList = $this->getAllowedPageList($results->totalNotFiltered);
        $query->limit = [] !== $results->pageList ? \min($query->limit, \max($results->pageList)) : $query->limit;
        $results->params = \array_merge($query->params(), $results->params);
        if ($query->callback) {
            return;
        }

        $results->columns = $this->getColumns();
        $results->attributes = \array_merge([
            'total-rows' => $results->filtered,
            'total-not-filtered' => $results->totalNotFiltered,
            'page-list' => \json_encode($results->pageList),
        ], $query->attributes(), $results->attributes);
    }

    /**
     * Create the columns.
     *
     * @return Column[] the columns
     *
     * @phpstan-return non-empty-array<int, Column>
     */
    private function createColumns(): array
    {
        $path = $this->getColumnDefinitions();
        $columns = Column::fromJson($this, $path);
        $columns[] = Column::createColumnAction();

        /** @phpstan-var non-empty-array<int, Column> */
        return $columns;
    }

    /**
     * Map the given entity or array.
     *
     * @param EntityType $objectOrArray the entity or array to map
     * @param Column[]   $columns       the columns
     *
     * @return array<string, string> the mapped entity or array
     */
    private function mapEntity(EntityInterface|array $objectOrArray, array $columns): array
    {
        return $this->mapToKeyValue(
            $columns,
            fn (Column $column): array => [$column->getAlias() => $column->mapValue($objectOrArray)]
        );
    }
}
