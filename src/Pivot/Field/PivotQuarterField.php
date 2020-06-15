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

namespace App\Pivot\Field;

/**
 * Pivot field that extract quarter (1 or 4).
 *
 * @author Laurent Muller
 */
class PivotQuarterField extends PivotDateField
{
    /**
     * @var callable|null
     */
    private $formatter;

    /**
     * Constructor.
     *
     * @param string $name  the field name
     * @param string $title the field title
     */
    public function __construct(string $name, ?string $title = null)
    {
        parent::__construct($name, self::PART_MONTH, $title);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayValue($value)
    {
        return $this->formatQuarter($value);
    }

    /**
     * Gets the callback used to format a quarter.
     *
     * @return callable|null the callback, if set; null otherwise
     */
    public function getFormatter(): ?callable
    {
        return $this->formatter;
    }

    /**
     * Sets callback used to format a quarter.
     *
     * The function receive the quarter (1 to 4) as parameter and must return a string.
     *
     * @param callable|null $formatter the callback to set; null to use default
     */
    public function setFormatter(?callable $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetValue(\DateTimeInterface $date)
    {
        $value = parent::doGetValue($date);

        return (int) \ceil($value / 3);
    }

    /**
     * Formats the quarter.
     *
     * @param int $quarter the quarter (1 to 4) to format
     *
     * @return string|null the formatted quarter
     */
    private function formatQuarter(int $quarter): ?string
    {
        if (\is_callable($this->formatter)) {
            return \call_user_func($this->formatter, $quarter);
        }
        switch ($quarter) {
            case 1:
                return '1st quarter';
            case 2:
                return '2nd quarter';
            case 3:
                return '3rd quarter';
            case 4:
                return '4th quarter';
            default:
                return (string) $quarter;
        }
    }
}
