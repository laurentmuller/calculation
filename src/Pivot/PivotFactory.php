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
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotField;
use Symfony\Component\Intl\Exception\UnexpectedTypeException;

/**
 * Factory to create a pivot table.
 *
 * @author Laurent Muller
 */
class PivotFactory
{
    /**
     * The aggregator class name.
     *
     * @var string
     */
    private $aggregatorClass;

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
     * The data source.
     *
     * @var array
     */
    private $dataset;

    /**
     * The key field.
     *
     * @var PivotField
     */
    private $keyField;

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
                    $title = $field->getTitle($key);
                    $aggregator = $this->createAggregator();
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
                    $title = $field->getTitle($key);
                    $aggregator = $this->createAggregator();
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
     * @param string
     */
    public function setAggregatorClass(string $aggregatorClass): self
    {
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
        return \array_reduce($fields, function (string $carry, PivotField $field) {
            if (\strlen($carry)) {
                return $carry . '\\' . $field->getHeaderName();
            } else {
                return $field->getHeaderName();
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
    private function createAggregator($value = null): Aggregator
    {
        return new $this->aggregatorClass($value);
    }
}
