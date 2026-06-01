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

use App\Pivot\Aggregator\AggregatorInterface;
use App\Pivot\Field\PivotField;
use Symfony\Component\Clock\DatePoint;

/**
 * Factory to create a pivot table.
 *
 * @phpstan-type RowType array<array-key, DatePoint|int|float|string>
 */
class PivotTableFactory
{
    /**
     * The column fields.
     *
     * @var PivotField[]
     */
    private array $columnFields = [];

    /** The data field. */
    private ?PivotField $dataField = null;

    /**
     * The row fields.
     *
     * @var PivotField[]
     */
    private array $rowFields = [];

    /**
     * @param RowType[] $dataset
     */
    public function __construct(
        private readonly array $dataset,
        private readonly PivotOperation $operation,
        private ?string $title = null
    ) {
    }

    /**
     * Creates the pivot table.
     *
     * @return ?PivotTable the pivot table or <code>null</code> if data are not valid
     */
    public function create(): ?PivotTable
    {
        if (!$this->isValid()) {
            return null;
        }

        $dataField = $this->dataField;
        $rowFields = $this->rowFields;
        $columnFields = $this->columnFields;
        $table = new PivotTable($this->operation, $this->title);
        $rootRow = $table->getRootRow();
        $rootColumn = $table->getRootColumn();

        // add cells
        foreach ($this->dataset as $row) {
            /** @var int|float|null $value */
            $value = $dataField->getValue($row);
            $currentRow = $this->setNodeValue($rowFields, $row, $rootRow, $value);
            $currentCol = $this->setNodeValue($columnFields, $row, $rootColumn, $value);
            $cell = $table->findCell($currentCol, $currentRow);
            if ($cell instanceof PivotCell) {
                $cell->addValue($value);
            } else {
                $aggregator = $this->createAggregator();
                $table->addCellValue($aggregator, $currentCol, $currentRow, $value);
            }
            $table->addValue($value);
        }

        // update titles
        $rootColumn->setTitle($this->buildFieldsTitle($columnFields));
        $rootRow->setTitle($this->buildFieldsTitle($rowFields));

        // update table
        $table->setColumnFields($columnFields)
            ->setRowFields($rowFields)
            ->setDataField($dataField);

        return $table;
    }

    /**
     * Gets the column fields.
     *
     * @return PivotField[]
     */
    public function getColumnFields(): array
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
     * Gets the dataset.
     */
    public function getDataset(): array
    {
        return $this->dataset;
    }

    /**
     * Gets the operation.
     */
    public function getOperation(): PivotOperation
    {
        return $this->operation;
    }

    /**
     * Gets the row fields.
     *
     * @return PivotField[]
     */
    public function getRowFields(): array
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
     * Creates a new instance.
     *
     * @param RowType[] $dataset
     */
    public static function instance(
        array $dataset,
        ?PivotOperation $operation = null,
        ?string $title = null
    ): self {
        return new self($dataset, $operation ?? PivotOperation::getDefault(), $title);
    }

    /**
     * Returns a value indicating if inputs are valid.
     *
     * @phpstan-assert-if-true non-empty-array<RowType> $this->dataset
     * @phpstan-assert-if-true non-empty-array<PivotField> $this->columnFields
     * @phpstan-assert-if-true non-empty-array<PivotField> $this->rowFields
     * @phpstan-assert-if-true PivotField $this->dataField
     */
    public function isValid(): bool
    {
        return !\in_array([], [$this->dataset, $this->columnFields, $this->rowFields], true)
            && $this->dataField instanceof PivotField;
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
     * Gets the title for the given fields.
     *
     * @param PivotField[] $fields the fields
     */
    private function buildFieldsTitle(array $fields): string
    {
        return \implode(
            PivotNode::PATH_SEPARATOR,
            \array_map(static fn (PivotField $field): string => $field->getTitle(), $fields)
        );
    }

    private function createAggregator(): AggregatorInterface
    {
        return $this->operation->createAggregator();
    }

    /**
     * Find or create a node and add the value.
     *
     * @param PivotField[] $fields
     */
    private function setNodeValue(array $fields, array $row, PivotNode $node, int|float|null $value): PivotNode
    {
        foreach ($fields as $field) {
            /** @var string|int $key */
            $key = $field->getValue($row);
            $child = $node->find($key);
            if ($child instanceof PivotNode) {
                $node = $child;
                continue;
            }
            $aggregator = $this->createAggregator();
            $title = (string) $field->getDisplayValue($key);
            $node = $node->add($aggregator, $key)
                ->setTitle($title);
        }
        $node->addValue($value);

        return $node;
    }
}
