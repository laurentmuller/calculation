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

use App\Pivot\Aggregator\AbstractAggregator;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotField;
use Symfony\Component\Intl\Exception\UnexpectedTypeException;

/**
 * Factory to create a pivot table.
 *
 * @author Laurent Muller
 */
class PivotTableFactory
{
    /**
     * The aggregator class name.
     *
     * @template T of AbstractAggregator
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
     * The data source.
     */
    private array $dataset;

    /**
     * The key field.
     */
    private ?PivotField $keyField = null;

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
    private ?string $title;

    /**
     * Constructor.
     *
     * @param array  $dataset the dataset where each entry is also an array of field/value
     * @param string $title   the table title
     */
    public function __construct(array $dataset, ?string $title = null)
    {
        $this->title = $title;
        $this->dataset = $dataset;
        $this->aggregatorClass = SumAggregator::class;
    }

    /**
     * Creates the pivot table.
     *
     * @return PivotTable|null the pivot table or null if data are not valid
     */
    public function create(): ?PivotTable
    {
        // check values
        if (!$this->isValid()) {
            return null;
        }

        $keys = [];
        $keyField = $this->keyField;
        $dataField = $this->dataField;
        $rowFields = $this->rowFields;
        $colFields = $this->columnFields;
        $table = new PivotTable($this->createAggregator(), $this->title);

        // build
        foreach ($this->dataset as $row) {
            // key
            if ($keyField) {
                $key = $keyField->getValue($row);
                if (\in_array($key, $keys, true)) {
                    continue;
                }
                $keys[] = $key;
            }

            // value
            $value = $dataField->getValue($row);

            // find or create columns
            $currentCol = $table->getColumn();
            foreach ($colFields as $field) {
                $key = $field->getValue($row);
                if (!$child = $currentCol->find($key)) {
                    $aggregator = $this->createAggregator();
                    $title = (string) $field->getDisplayValue($key);
                    $currentCol = $currentCol
                        ->add($aggregator, $key)
                        ->setTitle($title);
                } else {
                    $currentCol = $child;
                }
            }

            // update
            $currentCol->addValue($value);

            // find or create rows
            $currentRow = $table->getRow();
            foreach ($rowFields as $field) {
                $key = $field->getValue($row);
                if (!$child = $currentRow->find($key)) {
                    $aggregator = $this->createAggregator();
                    $title = (string) $field->getDisplayValue($key);
                    $currentRow = $currentRow
                        ->add($aggregator, $key)
                        ->setTitle($title);
                } else {
                    $currentRow = $child;
                }
            }

            // update
            $currentRow->addValue($value);

            // update or create cell
            if ($cell = $table->findCellByNode($currentCol, $currentRow)) {
                $cell->addValue($value);
            } else {
                $aggregator = $this->createAggregator();
                $table->addCellValue($aggregator, $currentCol, $currentRow, $value);
            }

            $table->addValue($value);
        }

        // fields
        $table->setKeyField($keyField)
            ->setDataField($dataField)
            ->setColumnFields($colFields)
            ->setRowFields($rowFields);

        // titles
        $table->getColumn()->setTitle($this->buildFieldsTitle($colFields));
        $table->getRow()->setTitle($this->buildFieldsTitle($rowFields));

        return $table;
    }

    /**
     * Gets the aggregator class name.
     */
    public function getAggregatorClass(): string
    {
        return $this->aggregatorClass;
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
     *
     * @return PivotField
     */
    public function getDataField(): ?PivotField
    {
        return $this->dataField;
    }

    /**
     * Gets the dataset.
     *
     * @return array
     */
    public function getDataset()
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
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Creates a new instance.
     *
     * @param array  $dataset the dataset
     * @param string $title   the table title
     */
    public static function instance(array $dataset, ?string $title = null): self
    {
        return new self($dataset, $title);
    }

    /**
     * Returns a value indicating if data are valid.
     *
     * @return bool true if valid
     */
    public function isValid(): bool
    {
        if (empty($this->dataset) || empty($this->columnFields) || empty($this->rowFields) || empty($this->dataField)) {
            return false;
        }

        return true;
    }

    /**
     * Sets the aggregator class name.
     *
     * @param string $aggregatorClass the aggregator class name to set
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the AbstractAggregator class
     */
    public function setAggregatorClass(string $aggregatorClass): self
    {
        if (!\is_subclass_of($aggregatorClass, AbstractAggregator::class)) {
            throw new \InvalidArgumentException(\sprintf('Expected argument of type "%s", "%s" given', AbstractAggregator::class, $aggregatorClass));
        }
        $this->aggregatorClass = $aggregatorClass;

        return $this;
    }

    /**
     * Sets the column fields.
     *
     * @param PivotField[]|PivotField $fields the fields to set
     *
     * @throws UnexpectedTypeException if one of the given fields is not an instanceof of the PivotField class
     */
    public function setColumnFields($fields): self
    {
        $this->columnFields = $this->checkFields($fields);

        return $this;
    }

    /**
     * Sets the data field.
     *
     * @param PivotField $dataField the data field to set
     */
    public function setDataField(PivotField $dataField): self
    {
        $this->dataField = $dataField;

        return $this;
    }

    /**
     * Sets the unique key field.
     */
    public function setKeyField(?PivotField $keyField): self
    {
        $this->keyField = $keyField;

        return $this;
    }

    /**
     * Sets the row fields.
     *
     * @param PivotField[]|PivotField $fields the fields to set
     *
     * @throws UnexpectedTypeException if one of the given fields is not an instanceof of the PivotField class
     */
    public function setRowFields($fields): self
    {
        $this->rowFields = $this->checkFields($fields);

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
     * Gets the title for the given fields.
     *
     * @param PivotField[] $fields the fields
     *
     * @return string the title
     */
    private function buildFieldsTitle(array $fields): string
    {
        return \array_reduce($fields, function (string $carry, PivotField $field): string {
            if (\strlen($carry)) {
                return $carry . '\\' . $field->getTitle();
            } else {
                return $field->getTitle();
            }
        }, '');
    }

    /**
     * Checks if all elements of the given array are instance of PivotField class.
     *
     * @param mixed $fields the array to validate
     *
     * @return PivotField[] the pivot fields
     *
     * @throws UnexpectedTypeException if one of the given fields is not an instanceof of the PivotField class
     */
    private function checkFields($fields): array
    {
        if (!\is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $field) {
            if (!$field instanceof PivotField) {
                throw new UnexpectedTypeException($field, PivotField::class);
            }
        }

        return $fields;
    }

    /**
     * Creates an aggregator.
     *
     * @param mixed $value the initial value
     */
    private function createAggregator($value = null): AbstractAggregator
    {
        /** @psalm-var class-string<AbstractAggregator> $class */
        $class = $this->aggregatorClass;

        return new $class($value);
    }
}
