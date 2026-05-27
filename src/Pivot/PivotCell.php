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

namespace App\Pivot;

use App\Pivot\Aggregator\AbstractAggregator;

/**
 * Represents a data cell.
 */
class PivotCell extends AbstractPivotAggregator
{
    /**
     * @param AbstractAggregator                $aggregator the aggregator function
     * @param PivotNode                         $column     the parent column
     * @param PivotNode                         $row        the parent row
     * @param AbstractAggregator|int|float|null $value      the initial value
     */
    public function __construct(
        AbstractAggregator $aggregator,
        private readonly PivotNode $column,
        private readonly PivotNode $row,
        AbstractAggregator|int|float|null $value = null
    ) {
        parent::__construct($aggregator, $value);
    }

    /**
     * Returns if this column and row keys are equal to the given keys.
     *
     * @param string|int $columnKey the column key to compare to
     * @param string|int $rowKey    the row key to compare to
     */
    public function equalsKey(string|int $columnKey, string|int $rowKey): bool
    {
        return $this->column->equalsKey($columnKey) && $this->row->equalsKey($rowKey);
    }

    /**
     * Returns if this parent column and parent row are equal to the given nodes.
     *
     * @param PivotNode $column the parent column to compare to
     * @param PivotNode $row    the parent row to compare to
     */
    public function equalsNode(PivotNode $column, PivotNode $row): bool
    {
        return $this->column === $column && $this->row === $row;
    }

    /**
     * Returns if this column and row paths are equal to the given paths.
     *
     * @param string $columnPath the column path to compare to
     * @param string $rowPath    the row path to compare to
     */
    public function equalsPath(string $columnPath, string $rowPath): bool
    {
        return $this->getColumnPath() === $columnPath && $this->getRowPath() === $rowPath;
    }

    /**
     * Gets the parent column.
     */
    public function getColumn(): PivotNode
    {
        return $this->column;
    }

    /**
     * Gets the imploded column path.
     */
    public function getColumnPath(string $separator = PivotTable::PATH_SEPARATOR): string
    {
        return $this->column->getPath($separator);
    }

    /**
     * Gets the imploded column titles.
     *
     * @param string $separator the separator to use between titles
     *
     * @see PivotNode::getTitles()
     */
    public function getColumnTitle(string $separator = PivotTable::PATH_SEPARATOR): string
    {
        return \implode($separator, $this->column->getTitles());
    }

    /**
     * Gets the parent row.
     */
    public function getRow(): PivotNode
    {
        return $this->row;
    }

    /**
     * Gets the imploded row path.
     */
    public function getRowPath(string $separator = PivotTable::PATH_SEPARATOR): string
    {
        return $this->row->getPath($separator);
    }

    /**
     * Gets the imploded row titles.
     *
     * @param string $separator the separator to use between titles
     *
     * @see PivotNode::getTitles()
     */
    public function getRowTitle(string $separator = PivotTable::PATH_SEPARATOR): string
    {
        return \implode($separator, $this->row->getTitles());
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'row' => $this->getRowTitle(),
            'column' => $this->getColumnTitle(),
            'value' => $this->aggregator->getRoundResult(),
        ];
    }
}
