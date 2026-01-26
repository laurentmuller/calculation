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

namespace App\Pivot\Field;

use Symfony\Component\Clock\DatePoint;

/**
 * Field for a date.
 */
class PivotDateField extends PivotField
{
    /**
     * Numeric representation of a month, without leading zeros.
     */
    public const string PART_MONTH = 'n';

    /**
     * ISO-8601 week number of year, weeks starting on Monday.
     */
    public const string PART_WEEK = 'W';

    /**
     * ISO-8601 numeric representation of the day of the week. 1 (for Monday) through 7 (for Sunday).
     */
    public const string PART_WEEK_DAY = 'N';

    /**
     * A full numeric representation of a year, 4 digits.
     */
    public const string PART_YEAR = 'Y';

    /**
     * @param string  $name   the field name
     * @param string  $format the format used to extract the date part
     * @param ?string $title  the field title
     */
    public function __construct(protected string $name, protected string $format, protected ?string $title = null)
    {
        parent::__construct($name, $title);
    }

    #[\Override]
    public function getValue(array $row): float|int|string|DatePoint|null
    {
        $value = $this->getRowValue($row);
        if ($value instanceof DatePoint) {
            return $this->doGetValue($value);
        }

        return parent::getValue($row);
    }

    /**
     * Gets the value for the given date.
     */
    protected function doGetValue(DatePoint $date): int
    {
        return (int) $date->format($this->format);
    }
}
