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

namespace App\DataTables\Columns;

use App\DataTables\Tables\AbstractDataTable;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data column for boolean values.
 *
 * @author Laurent Muller
 */
class BooleanColumn extends AbstractColumn
{
    /**
     * The value to display when false.
     *
     * @var string
     */
    protected $falseValue = 'false';

    /**
     * The value to display when true.
     *
     * @var string
     */
    protected $trueValue = 'true';

    /**
     * Constructor.
     *
     * @param AbstractDataTable $table   the parent table
     * @param string            $name    the field name
     * @param array             $options the additional options
     */
    public function __construct(AbstractDataTable $table, string $name, array $options = [])
    {
        parent::__construct($table, $name, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function formatValue($value): string
    {
        if (null === $value) {
            return parent::formatValue($value);
        }

        return (bool) $value ? $this->trueValue : $this->falseValue;
    }

    /**
     * Gets the value to display when false.
     */
    public function getFalseValue(): string
    {
        return $this->falseValue;
    }

    /**
     * Gets the value to display when true.
     */
    public function getTrueValue(): string
    {
        return $this->trueValue;
    }

    /**
     * Sets the value to display when false.
     */
    public function setFalseValue(string $falseValue): self
    {
        $this->falseValue = $falseValue;

        return $this;
    }

    /**
     * Sets the value to display when true.
     */
    public function setTrueValue(string $trueValue): self
    {
        $this->trueValue = $trueValue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault('trueValue', 'True')
            ->setDefault('falseValue', 'False')
            ->setDefault('nullValue', 'Null')
            ->setAllowedTypes('trueValue', 'string')
            ->setAllowedTypes('falseValue', 'string')
            ->setAllowedTypes('nullValue', 'string');

        return $this;
    }
}
