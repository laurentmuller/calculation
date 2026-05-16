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

/**
 * Factory to create a pivot table.
 */
class PivotTableFactory
{
    /** The title separator. */
    private const string SEPARATOR = '\\';

    /**
     * The aggregator class name.
     *
     * @var class-string<AbstractAggregator>
     */
    private readonly string $aggregator;

    /**
     * The column fields.
     *
     * @var PivotField[]
     */
    private array $columnFields = [];

    /** The data field. */
    private ?PivotField $dataField = null;

    /** The key field. */
    private ?PivotField $keyField = null;

    /**
     * The row fields.
     *
     * @var PivotField[]
     */
    private array $rowFields = [];

    /**
     * @param array<array<array-key, mixed>> $dataset
     */
    public function __construct(
        private readonly array $dataset,
        PivotOperation $operation = PivotOperation::SUM,
        private ?string $title = null
    ) {
        $this->aggregator = $operation->getAggregator();
    }

    /**
     * Creates the pivot table.
     *
     * @return PivotTable|null the pivot table or <code>null</code> if data are not valid
     */
    public function create(): ?PivotTable
    {
        if (!$this->isValid()) {
            return null;
        }

        $keys = [];
        $keyField = $this->keyField;
        $dataField = $this->dataField;
        $rowFields = $this->rowFields;
        $columnFields = $this->columnFields;
        $table = new PivotTable($this->createAggregator(), $this->title);

        foreach ($this->dataset as $row) {
            // key
            if ($keyField instanceof PivotField) {
                $key = $keyField->getValue($row);
                if (\in_array($key, $keys, true)) {
                    continue;
                }
                $keys[] = $key;
            }
            $value = $dataField?->getValue($row);
            $currentCol = $this->setNodeValue($columnFields, $row, $table->getRootColumn(), $value);
            $currentRow = $this->setNodeValue($rowFields, $row, $table->getRootRow(), $value);
            $cell = $table->findCellByNode($currentCol, $currentRow);
            if ($cell instanceof PivotCell) {
                $cell->addValue($value);
            } else {
                $table->addCellValue($this->createAggregator(), $currentCol, $currentRow, $value);
            }
            $table->addValue($value);
        }

        $this->updateKeyField($table, $keyField)
            ->updateDataField($table, $dataField)
            ->updateRowFields($table, $rowFields)
            ->updateColumnFields($table, $columnFields);
        $table->getRootColumn()->setTitle($this->buildFieldsTitle($columnFields));
        $table->getRootRow()->setTitle($this->buildFieldsTitle($rowFields));

        return $table;
    }

    /**
     * Gets the aggregator class name.
     *
     * @return class-string<AbstractAggregator>
     */
    public function getAggregator(): string
    {
        return $this->aggregator;
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
     * Returns the unique key field.
     */
    public function getKeyField(): ?PivotField
    {
        return $this->keyField;
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
     * @param array<array<array-key, mixed>> $dataset
     */
    public static function instance(
        array $dataset,
        PivotOperation $operation = PivotOperation::SUM,
        ?string $title = null
    ): self {
        return new self($dataset, $operation, $title);
    }

    /**
     * Returns a value indicating if data are valid.
     */
    public function isValid(): bool
    {
        return !\in_array([], [$this->dataset, $this->columnFields, $this->rowFields], true)
            && $this->dataField instanceof PivotField;
    }

    /**
     * Sets the column fields.
     *
     * @param PivotField ...$fields the fields to set
     */
    public function setColumnFields(PivotField ...$fields): static
    {
        $this->columnFields = $fields;

        return $this;
    }

    /**
     * Sets the data field.
     *
     * @param PivotField $dataField the data field to set
     */
    public function setDataField(PivotField $dataField): static
    {
        $this->dataField = $dataField;

        return $this;
    }

    /**
     * Sets the unique key field.
     */
    public function setKeyField(?PivotField $keyField): static
    {
        $this->keyField = $keyField;

        return $this;
    }

    /**
     * Sets the row fields.
     *
     * @param PivotField ...$fields the fields to set
     */
    public function setRowFields(PivotField ...$fields): static
    {
        $this->rowFields = $fields;

        return $this;
    }

    /**
     * Sets the table title.
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the title for the given fields.
     *
     * @param PivotField[] $fields the fields
     *
     * @return string the title
     */
    private function buildFieldsTitle(array $fields): string
    {
        return \implode(
            self::SEPARATOR,
            \array_map(static fn (PivotField $field): string => (string) $field->getTitle(), $fields)
        );
    }

    /**
     * Creates an aggregator.
     */
    private function createAggregator(): AbstractAggregator
    {
        return new $this->aggregator();
    }

    /**
     * Find or create a node and update the value.
     *
     * @param PivotField[] $fields
     */
    private function setNodeValue(array $fields, array $row, PivotNode $node, mixed $value): PivotNode
    {
        foreach ($fields as $field) {
            $key = $field->getValue($row);
            $child = $node->find($key);
            if (!$child instanceof PivotNode) {
                $aggregator = $this->createAggregator();
                $title = (string) $field->getDisplayValue($key);
                $node = $node
                    ->add($aggregator, $key)
                    ->setTitle($title);
            } else {
                $node = $child;
            }
        }
        $node->addValue($value);

        return $node;
    }

    /**
     * @param PivotField[] $columnFields
     */
    private function updateColumnFields(PivotTable $table, array $columnFields): void
    {
        if ([] !== $columnFields) {
            $table->setColumnFields($columnFields);
        }
    }

    private function updateDataField(PivotTable $table, ?PivotField $dataField): static
    {
        if ($dataField instanceof PivotField) {
            $table->setDataField($dataField);
        }

        return $this;
    }

    private function updateKeyField(PivotTable $table, ?PivotField $keyField): static
    {
        if ($keyField instanceof PivotField) {
            $table->setKeyField($keyField);
        }

        return $this;
    }

    /**
     * @param PivotField[] $rowFields
     */
    private function updateRowFields(PivotTable $table, array $rowFields): static
    {
        if ([] !== $rowFields) {
            $table->setRowFields($rowFields);
        }

        return $this;
    }
}
