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
use App\Utils\FormatUtils;

/**
 * The abstract table.
 *
 * @psalm-import-type EntityType from Column
 */
abstract class AbstractTable implements SortModeInterface
{
    /**
     * The column definitions.
     *
     * @var ?Column[]
     */
    private ?array $columns = null;

    public function formatAmount(float $value): string
    {
        return FormatUtils::formatAmount($value);
    }

    public function formatDate(\DateTimeInterface $value): string
    {
        return FormatUtils::formatDate($value);
    }

    /**
     * @psalm-api
     */
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
     */
    public function getColumns(): array
    {
        if (null === $this->columns) {
            $this->columns = $this->createColumns();
        }

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
        foreach ($columns as $column) {
            if ($column->isDefault()) {
                return $column;
            }
        }
        foreach ($columns as $column) {
            if ($column->isVisible()) {
                return $column;
            }
        }

        return null;
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
        $results->params = \array_merge($query->parameters(), $results->params);
        if ($query->callback) {
            return;
        }
        $results->columns = $this->getColumns();
        $results->attributes = \array_merge([
            'total-rows' => $results->filtered,
            'total-not-filtered' => $results->totalNotFiltered,
            'page-list' => $this->implodePageList($results->pageList),
        ], $query->attributes(), $results->attributes);
    }

    /**
     * Create the columns.
     *
     * @return Column[] the columns
     */
    private function createColumns(): array
    {
        $path = $this->getColumnDefinitions();
        $columns = Column::fromJson($this, $path);
        $columns[] = Column::createColumnAction();

        return $columns;
    }

    /**
     * Implode the given page list.
     *
     * @param int[] $pageList the page list
     */
    private function implodePageList(array $pageList): string
    {
        return '[' . \implode(',', $pageList) . ']';
    }

    /**
     * Map the given entity or array to an array.
     *
     * @param EntityType $objectOrArray the entity or array to map
     * @param Column[]   $columns       the columns
     *
     * @return array<string, string> the mapped entity or array
     */
    private function mapEntity(EntityInterface|array $objectOrArray, array $columns): array
    {
        return \array_reduce(
            $columns,
            /** @psalm-param array<string, string> $result */
            static function (array $result, Column $column) use ($objectOrArray): array {
                $result[$column->getAlias()] = $column->mapValue($objectOrArray);

                return $result;
            },
            []
        );
    }
}
