<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pivot;

use App\Pivot\Aggregator\Aggregator;
use App\Utils\Utils;

/**
 * The pivot table.
 *
 * @author Laurent Muller
 */
class PivotTable extends PivotAggregator
{
    /**
     * The cell data.
     *
     * @var PivotCell[]
     */
    private $cells = [];

    /**
     * The root column.
     *
     * @var PivotNode
     */
    private $rootCol;

    /**
     * The root row.
     *
     * @var PivotNode
     */
    private $rootRow;

    /**
     * The title.
     *
     * @var string
     */
    private $title;

    /**
     * The total title.
     *
     * @var string
     */
    private $totalTitle;

    /**
     * Constructor.
     *
     * @param Aggregator $aggregator the aggregator function
     * @param string     $title      the table title
     */
    public function __construct(Aggregator $aggregator, ?string $title = null)
    {
        parent::__construct($aggregator);

        $this->rootCol = new PivotNode(clone $aggregator);
        $this->rootRow = new PivotNode(clone $aggregator);
        $this->title = $title;
    }

    /**
     * Adds a cell.
     *
     * @param PivotCell $cell the cell to add
     */
    public function addCell(PivotCell $cell): self
    {
        $this->cells[] = $cell;

        return $this;
    }

    /**
     * Creates and adds a cell.
     *
     * @param Aggregator $aggregator the aggregator
     * @param PivotNode  $column     the parent column
     * @param PivotNode  $row        the parent row
     * @param mixed      $value      the initial value
     *
     * @return PivotCell the newly created cell-
     */
    public function addCellValue(Aggregator $aggregator, PivotNode $column, PivotNode $row, $value = null): PivotCell
    {
        $cell = new PivotCell($aggregator, $column, $row, $value);
        $this->addCell($cell);

        return $cell;
    }

    /**
     * Finds a pivot cell for the given keys.
     *
     * @param mixed $columnKey the column key to search for
     * @param mixed $rowKey    the row key to search for
     *
     * @return PivotCell|null the cell, if found; null otherwise
     */
    public function findCellByKey($columnKey, $rowKey): ?PivotCell
    {
        foreach ($this->cells as $cell) {
            if ($cell->equalsKey($columnKey, $rowKey)) {
                return $cell;
            }
        }

        return null;
    }

    /**
     * Finds a pivot cell for the given nodes.
     *
     * @param PivotNode $column the node column to search for
     * @param PivotNode $row    the node row to search for
     *
     * @return PivotCell|null the cell, if found; null otherwise
     */
    public function findCellByNode(PivotNode $column, PivotNode $row): ?PivotCell
    {
        foreach ($this->cells as $cell) {
            if ($cell->equalsNode($column, $row)) {
                return $cell;
            }
        }

        return null;
    }

    /**
     * Finds a pivot cell for the given paths.
     *
     * @param string $columnPath the column path to search for
     * @param string $rowPath    the row path to search for
     *
     * @return PivotCell|null the cell, if found; null otherwise
     */
    public function findCellByPath(string $columnPath, string $rowPath): ?PivotCell
    {
        foreach ($this->cells as $cell) {
            if ($cell->equalsPath($columnPath, $rowPath)) {
                return $cell;
            }
        }

        return null;
    }

    /**
     * Gets the cells.
     *
     * @return PivotCell[]
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * Gets the root column.
     */
    public function getColumn(): PivotNode
    {
        return $this->rootCol;
    }

    /**
     * Gets the root row.
     */
    public function getRow(): PivotNode
    {
        return $this->rootRow;
    }

    /**
     * Gets the table title.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Gets the total title.
     *
     * @return string
     */
    public function getTotalTitle(): ?string
    {
        return $this->totalTitle;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $result = [];
        if ($this->title) {
            $result['title'] = $this->title;
        }
        $result['aggregator'] = Utils::getShortName($this->aggregator);
        if (!empty($this->getValue())) {
            $result['value'] = $this->aggregator->getFormattedResult();
        }

        return \array_merge($result, [
            'column' => $this->rootCol,
            'row' => $this->rootRow,
            'cells' => $this->cells,
        ]);
    }

    /**
     * Sets the table title.
     *
     * @param string $title
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets the total title.
     *
     * @param string $totalTitle
     */
    public function setTotalTitle(?string $totalTitle): self
    {
        $this->totalTitle = $totalTitle;

        return $this;
    }
}
