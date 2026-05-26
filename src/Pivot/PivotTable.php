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
use App\Traits\ArrayTrait;
use App\Utils\StringUtils;

/**
 * The pivot table.
 */
class PivotTable extends AbstractPivotAggregator
{
    use ArrayTrait;

    /** The default path separator. */
    public const string PATH_SEPARATOR = \DIRECTORY_SEPARATOR;

    /**
     * The cell data.
     *
     * @var array<int, PivotCell>
     */
    private array $cells = [];

    /**
     * The column fields.
     *
     * @var PivotField[]
     */
    private array $columnFields = [];

    /** The data field. */
    private ?PivotField $dataField = null;

    /** The root column. */
    private readonly PivotNode $rootColumn;

    /** The root row. */
    private readonly PivotNode $rootRow;

    /**
     * The row fields.
     *
     * @var PivotField[]
     */
    private array $rowFields = [];

    /** The total title. */
    private ?string $totalTitle = null;

    public function __construct(PivotOperation $operation, private ?string $title = null)
    {
        parent::__construct($operation->createAggregator());
        $this->rootColumn = new PivotNode($operation->createAggregator());
        $this->rootRow = new PivotNode($operation->createAggregator());
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
     * @param mixed              $value      the initial value
     *
     * @return PivotCell the newly created cell
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
     * @return ?PivotCell the cell, if found; null otherwise
     */
    public function findCellByKey(mixed $columnKey, mixed $rowKey): ?PivotCell
    {
        return $this->findFirst($this->cells, static fn (PivotCell $cell): bool => $cell->equalsKey($columnKey, $rowKey));
    }

    /**
     * Finds a pivot cell for the given nodes.
     *
     * @param PivotNode $column the node column to search for
     * @param PivotNode $row    the node row to search for
     *
     * @return ?PivotCell the cell, if found; null otherwise
     */
    public function findCellByNode(PivotNode $column, PivotNode $row): ?PivotCell
    {
        return $this->findFirst($this->cells, static fn (PivotCell $cell): bool => $cell->equalsNode($column, $row));
    }

    /**
     * Finds a pivot cell for the given paths.
     *
     * @param string $columnPath the column path to search for
     * @param string $rowPath    the row path to search for
     *
     * @return ?PivotCell the cell, if found; null otherwise
     */
    public function findCellByPath(string $columnPath, string $rowPath): ?PivotCell
    {
        return $this->findFirst($this->cells, static fn (PivotCell $cell): bool => $cell->equalsPath($columnPath, $rowPath));
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
     * Gets the column fields.
     *
     * @return ?PivotField[]
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
     * Gets the maximum level of the root column.
     */
    public function getMaxColumnLevel(): int
    {
        return $this->rootColumn->getMaxLevel();
    }

    /**
     * Gets the maximum level of the root row.
     */
    public function getMaxRowLevel(): int
    {
        return $this->rootRow->getMaxLevel();
    }

    /**
     * Gets the root column.
     */
    public function getRootColumn(): PivotNode
    {
        return $this->rootColumn;
    }

    /**
     * Gets the root row.
     */
    public function getRootRow(): PivotNode
    {
        return $this->rootRow;
    }

    /**
     * Gets the row fields.
     *
     * @return ?PivotField[]
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

    #[\Override]
    public function jsonSerialize(): array
    {
        return \array_filter([
            'title' => $this->title,
            'aggregator' => StringUtils::getShortName($this->aggregator),
            'value' => $this->aggregator->getRoundResult(),
            'dataField' => $this->dataField,
            'columnFields' => $this->columnFields,
            'rowFields' => $this->rowFields,
            'rootColumn' => $this->rootColumn,
            'rootRow' => $this->rootRow,
            'cells' => $this->cells,
        ]);
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
}
