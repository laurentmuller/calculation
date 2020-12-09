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

namespace App\Pivot;

use App\Pivot\Aggregator\Aggregator;
use App\Pivot\Field\PivotField;
use App\Util\Utils;

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
     * The column fields.
     *
     * @var PivotField[]
     */
    private $columnFields = [];

    /**
     * The data field.
     *
     * @var PivotField
     */
    private $dataField;

    /**
     * The key field.
     *
     * @var PivotField
     */
    private $keyField;

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
     * The row fields.
     *
     * @var PivotField[]
     */
    private $rowFields = [];

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
     * Gets the column fields.
     *
     * @return PivotField[]
     */
    public function getColumnFields(): ?array
    {
        return $this->columnFields;
    }

    /**
     * Gets the data field.
     *
     * @return PivotField
     */
    public function getDataField(): ?PivotField
    {
        return $this->dataField;
    }

    /**
     * Gets the key field.
     *
     * @return PivotField
     */
    public function getKeyField(): ?PivotField
    {
        return $this->keyField;
    }

    /**
     * Gets the root row.
     */
    public function getRow(): PivotNode
    {
        return $this->rootRow;
    }

    /**
     * Gets the row fields.
     *
     * @return PivotField[]
     */
    public function getRowFields(): ?array
    {
        return $this->rowFields;
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
        $this->serialize($result, 'title', $this->title)

            ->serialize($result, 'aggregator', Utils::getShortName($this->aggregator))
            ->serialize($result, 'value', $this->aggregator->getFormattedResult())
            ->serialize($result, 'keyField', $this->keyField)

            ->serialize($result, 'dataField', $this->dataField)
            ->serialize($result, 'columnFields', $this->columnFields)
            ->serialize($result, 'rowFields', $this->rowFields)

            ->serialize($result, 'column', $this->rootCol)
            ->serialize($result, 'row', $this->rootRow)
            ->serialize($result, 'cells', $this->cells);

        return $result;
    }

    /**
     * Sets the column fields.
     *
     * @param PivotField[] $columnFields
     */
    public function setColumnFields(array $columnFields): self
    {
        $this->columnFields = $columnFields;

        return $this;
    }

    /**
     * Sets the data field.
     */
    public function setDataField(PivotField $dataField): self
    {
        $this->dataField = $dataField;

        return $this;
    }

    /**
     * Sets the  key field.
     */
    public function setKeyField(PivotField $keyField): self
    {
        $this->keyField = $keyField;

        return $this;
    }

    /**
     * Sets the row fields.
     *
     * @param PivotField[] $rowFields
     */
    public function setRowFields(array $rowFields): self
    {
        $this->rowFields = $rowFields;

        return $this;
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

    /**
     * Serialize a value. Do nothing if the value is null.
     *
     * @param array  $result the array to update
     * @param string $name   the variable name
     * @param mixed  $value  the value to put
     */
    private function serialize(array &$result, string $name, $value): self
    {
        if ($value) {
            $result[$name] = $value;
        }

        return $this;
    }
}
