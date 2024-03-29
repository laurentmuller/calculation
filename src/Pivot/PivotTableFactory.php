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
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotField;
use App\Traits\CheckSubClassTrait;
use Symfony\Component\Intl\Exception\UnexpectedTypeException;

/**
 * Factory to create a pivot table.
 *
 * @template T of AbstractAggregator
 */
class PivotTableFactory
{
    use CheckSubClassTrait;

    /**
     * The aggregator class name.
     *
     * @psalm-var class-string<T> $aggregatorClass
     */
    private string $aggregatorClass;

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
     * The row fields.
     *
     * @var PivotField[]
     */
    private array $rowFields = [];

    /**
     * @psalm-param class-string<T>|null $aggregatorClass
     *
     * @throws \InvalidArgumentException if the given aggregator class name is not a subclass of the AbstractAggregator class
     */
    public function __construct(private readonly array $dataset, private ?string $title = null, ?string $aggregatorClass = null)
    {
        $aggregatorClass ??= SumAggregator::class;
        $this->checkSubClass($aggregatorClass, AbstractAggregator::class);
        $this->aggregatorClass = $aggregatorClass;
    }

    /**
     * Creates the pivot table.
     *
     * @return PivotTable|null the pivot table or null if data are not valid
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
        /** @psalm-var array $row */
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
            $currentCol = $this->setNodeValue($columnFields, $row, $table->getColumn(), $value);
            $currentRow = $this->setNodeValue($rowFields, $row, $table->getRow(), $value);
            $cell = $table->findCellByNode($currentCol, $currentRow);
            if ($cell instanceof PivotCell) {
                $cell->addValue($value);
            } else {
                $aggregator = $this->createAggregator();
                $table->addCellValue($aggregator, $currentCol, $currentRow, $value);
            }
            $table->addValue($value);
        }
        $this->updateKeyField($table, $keyField)
            ->updateDataField($table, $dataField)
            ->updateRowFields($table, $rowFields)
            ->updateColumnFields($table, $columnFields);
        $table->getColumn()->setTitle($this->buildFieldsTitle($columnFields));
        $table->getRow()->setTitle($this->buildFieldsTitle($rowFields));

        return $table;
    }

    /**
     * Gets the aggregator class name.
     *
     * @psalm-return class-string<T>
     *
     * @psalm-api
     */
    public function getAggregatorClass(): string
    {
        return $this->aggregatorClass;
    }

    /**
     * Gets the column fields.
     *
     * @return PivotField[]
     *
     * @psalm-api
     */
    public function getColumnFields(): array
    {
        return $this->columnFields;
    }

    /**
     * Gets the data field.
     *
     * @psalm-api
     */
    public function getDataField(): ?PivotField
    {
        return $this->dataField;
    }

    /**
     * Gets the dataset.
     *
     * @psalm-api
     */
    public function getDataset(): array
    {
        return $this->dataset;
    }

    /**
     * Returns the unique key field.
     *
     * @psalm-api
     */
    public function getKeyField(): ?PivotField
    {
        return $this->keyField;
    }

    /**
     * Gets the row fields.
     *
     * @return PivotField[]
     *
     * @psalm-api
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
     * @psalm-template E of AbstractAggregator
     *
     * @psalm-param class-string<E>|null $aggregatorClass
     *
     * @psalm-return PivotTableFactory<E>
     */
    public static function instance(array $dataset, ?string $title = null, ?string $aggregatorClass = null): self
    {
        return new self($dataset, $title, $aggregatorClass);
    }

    /**
     * Returns a value indicating if data are valid.
     */
    public function isValid(): bool
    {
        return [] !== $this->dataset && [] !== $this->columnFields && [] !== $this->rowFields && $this->dataField instanceof PivotField;
    }

    /**
     * Sets the aggregator class name.
     *
     * @param string $aggregatorClass the aggregator class name to set
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the AbstractAggregator class
     *
     * @psalm-param class-string<T> $aggregatorClass
     *
     * @psalm-return PivotTableFactory<T>
     *
     * @psalm-api
     */
    public function setAggregatorClass(string $aggregatorClass): self
    {
        $this->checkSubClass($aggregatorClass, AbstractAggregator::class);
        $this->aggregatorClass = $aggregatorClass;

        return $this;
    }

    /**
     * Sets the column fields.
     *
     * @param PivotField|PivotField[] $fields the fields to set
     *
     * @psalm-return PivotTableFactory<T>
     *
     * @throws UnexpectedTypeException if one of the given fields is not an instanceof of the PivotField class
     */
    public function setColumnFields(array|PivotField $fields): self
    {
        $this->columnFields = $this->checkFields($fields);

        return $this;
    }

    /**
     * Sets the data field.
     *
     * @param PivotField $dataField the data field to set
     *
     * @psalm-return PivotTableFactory<T>
     */
    public function setDataField(PivotField $dataField): self
    {
        $this->dataField = $dataField;

        return $this;
    }

    /**
     * Sets the unique key field.
     *
     * @psalm-return PivotTableFactory<T>
     */
    public function setKeyField(?PivotField $keyField): self
    {
        $this->keyField = $keyField;

        return $this;
    }

    /**
     * Sets the row fields.
     *
     * @param PivotField|PivotField[] $fields the fields to set
     *
     * @psalm-return PivotTableFactory<T>
     *
     * @throws UnexpectedTypeException if one of the given fields is not an instanceof of the PivotField class
     */
    public function setRowFields(array|PivotField $fields): self
    {
        $this->rowFields = $this->checkFields($fields);

        return $this;
    }

    /**
     * Sets the table title.
     *
     * @psalm-return PivotTableFactory<T>
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
     *
     * @return string the title
     */
    private function buildFieldsTitle(array $fields): string
    {
        return \array_reduce($fields, function (string $carry, PivotField $field): string {
            $title = (string) $field->getTitle();
            if ('' !== $carry) {
                return $carry . '\\' . $title;
            }

            return $title;
        }, '');
    }

    /**
     * Checks if all elements of the given array are instance of PivotField class.
     *
     * @param mixed $fields the single value or an array to validate
     *
     * @return PivotField[] the pivot fields
     *
     * @throws UnexpectedTypeException if one of the given fields is not an instanceof of the PivotField class
     */
    private function checkFields(mixed $fields): array
    {
        if (!\is_array($fields)) {
            $fields = [$fields];
        }
        /** @var PivotField[] $result */
        $result = [];
        foreach ($fields as $field) {
            if (!$field instanceof PivotField) {
                throw new UnexpectedTypeException($field, PivotField::class);
            }
            $result[] = $field;
        }

        return $result;
    }

    /**
     * Creates an aggregator.
     */
    private function createAggregator(): AbstractAggregator
    {
        /** @psalm-var class-string<AbstractAggregator> $class */
        $class = $this->aggregatorClass;

        return new $class();
    }

    /**
     * Find or create node and update value.
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
     * @psalm-param PivotField[] $columnFields
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
     * @psalm-param PivotField[] $rowFields
     */
    private function updateRowFields(PivotTable $table, array $rowFields): static
    {
        if ([] !== $rowFields) {
            $table->setRowFields($rowFields);
        }

        return $this;
    }
}
