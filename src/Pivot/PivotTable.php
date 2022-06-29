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
use App\Pivot\Field\PivotField;
use App\Util\Utils;

/**
 * The pivot table.
 */
class PivotTable extends AbstractPivotAggregator
{
    /**
     * The default path separator.
     */
    final public const PATH_SEPARATOR = \DIRECTORY_SEPARATOR;

    /**
     * The cell data.
     *
     * @var PivotCell[]
     */
    private array $cells = [];

    /**
     * The column fields.
     *
     * @var PivotField[]
     */
    private array $columnFields = [];

    /**
     * The data field.
     */
    private ?PivotField $dataField = null;

    /**
     * The key field.
     */
    private ?PivotField $keyField = null;

    /**
     * The root column.
     */
    private PivotNode $rootCol;

    /**
     * The root row.
     */
    private PivotNode $rootRow;

    /**
     * The row fields.
     *
     * @var PivotField[]
     */
    private array $rowFields = [];

    /**
     * The total title.
     */
    private ?string $totalTitle = null;

    /**
     * Constructor.
     *
     * @param AbstractAggregator $aggregator the aggregator function
     * @param string|null        $title      the table title
     */
    public function __construct(protected AbstractAggregator $aggregator, private ?string $title = null)
    {
        parent::__construct($aggregator);

        $this->rootCol = new PivotNode(clone $aggregator);
        $this->rootRow = new PivotNode(clone $aggregator);
    }

    /**
     * Adds a cell.
     */
    public function addCell(PivotCell $cell): self
    {
        $this->cells[] = $cell;

        return $this;
    }

    /**
     * Creates and adds a cell.
     *
     * @param AbstractAggregator $aggregator the aggregator
     * @param PivotNode          $column     the parent column
     * @param PivotNode          $row        the parent row
     * @param mixed|null         $value      the initial value
     *
     * @return PivotCell the newly created cell-
     */
    public function addCellValue(AbstractAggregator $aggregator, PivotNode $column, PivotNode $row, mixed $value = null): PivotCell
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
    public function findCellByKey(mixed $columnKey, mixed $rowKey): ?PivotCell
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
     * @return PivotField[]|null
     */
    public function getColumnFields(): ?array
    {
        return $this->columnFields;
    }

    /**
     * Gets the data field.
     */
    public function getDataField(): ?PivotField
    {
        return $this->dataField;
    }

    /**
     * Gets the key field.
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
     * @return PivotField[]|null
     */
    public function getRowFields(): ?array
    {
        return $this->rowFields;
    }

    /**
     * Gets the table title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Gets the total title.
     */
    public function getTotalTitle(): ?string
    {
        return $this->totalTitle;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    public function jsonSerialize(): array
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
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets the total title.
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
     * @param string $name   the array key
     * @param mixed  $value  the array value
     *
     * @psalm-suppress MixedAssignment
     */
    private function serialize(array &$result, string $name, mixed $value): self
    {
        if ($value) {
            $result[$name] = $value;
        }

        return $this;
    }
}
