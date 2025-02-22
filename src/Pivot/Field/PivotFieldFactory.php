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

/**
 * Factory to create PivotField.
 */
class PivotFieldFactory
{
    /**
     * Creates a new instance.
     *
     * @param string      $name   the field name
     * @param ?string     $title  the field title
     * @param PivotMethod $method the field method (one of the <code></code>PivotField::METHOD_XX</code>)
     */
    public static function default(string $name, ?string $title = null, PivotMethod $method = PivotMethod::STRING): PivotField
    {
        return (new PivotField($name, $title))->setMethod($method);
    }

    /**
     * Creates a new instance with the <code>PivotMethod::FLOAT</code>.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function float(string $name, ?string $title = null): PivotField
    {
        return self::default($name, $title, PivotMethod::FLOAT);
    }

    /**
     * Creates a new instance with the <code>PivotMethod::INTEGER</code>.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function integer(string $name, ?string $title = null): PivotField
    {
        return self::default($name, $title, PivotMethod::INTEGER);
    }

    /**
     * Creates a new month instance.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     * @param bool    $short true to display the short month name, false to display the full month name
     */
    public static function month(string $name, ?string $title = null, bool $short = false): PivotMonthField
    {
        return new PivotMonthField($name, $title, $short);
    }

    /**
     * Creates a new date instance for quarter.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function quarter(string $name, ?string $title = null): PivotQuarterField
    {
        return new PivotQuarterField($name, $title);
    }

    /**
     * Creates a new date instance for the semester.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function semester(string $name, ?string $title = null): PivotSemesterField
    {
        return new PivotSemesterField($name, $title);
    }

    /**
     * Creates a new date instance for week number.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function week(string $name, ?string $title = null): PivotDateField
    {
        return new PivotDateField($name, PivotDateField::PART_WEEK, $title);
    }

    /**
     * Creates a new week day instance.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     * @param bool    $short true to display the short day name, false to display the full-day name
     */
    public static function weekday(string $name, ?string $title = null, bool $short = false): PivotWeekdayField
    {
        return new PivotWeekdayField($name, $title, $short);
    }

    /**
     * Creates a new date instance for the year.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function year(string $name, ?string $title = null): PivotDateField
    {
        return new PivotDateField($name, PivotDateField::PART_YEAR, $title);
    }
}
