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
     */
    protected string $format;

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
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(array $row)
    {
        /** @psalm-var mixed $value */
        $value = $this->getRowValue($row);
        if ($value instanceof \DateTimeInterface) {
            return $this->doGetValue($value);
        }

        return parent::getValue($row);
    }

    /**
     * Gets the value for the given date.
     *
     * @param \DateTimeInterface $date the date
     *
     * @return int
     */
    protected function doGetValue(\DateTimeInterface $date)
    {
        return (int) $date->format($this->format);
    }
}
