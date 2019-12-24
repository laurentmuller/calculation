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
 * Field for a date.
 *
 * @author Laurent Muller
 */
class PivotDateField extends PivotField
{
    /**
     * Numeric representation of a month, without leading zeros.
     */
    public const PART_MONTH = 'n';

    /**
     * ISO-8601 week number of year, weeks starting on Monday.
     */
    public const PART_WEEK = 'W';

    /**
     * ISO-8601 numeric representation of the day of the week. 1 (for Monday) through 7 (for Sunday).
     */
    public const PART_WEEK_DAY = 'N';

    /**
     * A full numeric representation of a year, 4 digits.
     */
    public const PART_YEAR = 'Y';

    /**
     * The date format.
     *
     * @var string
     */
    protected $format;

    /**
     * Constructor.
     *
     * @param string $name   the field name
     * @param string $format the format used to extract the date part
     * @param string $title  the field title
     */
    public function __construct(string $name, string $format, ?string $title = null)
    {
        parent::__construct($name, $title);
        $this->method = self::METHOD_INTEGER;
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(array $row)
    {
        if ($value = $row[$this->name]) {
            if ($value instanceof \DateTimeInterface) {
                return (int) $value->format($this->format);
            }
        }

        return null;
    }
}
