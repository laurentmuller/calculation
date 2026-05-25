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
     * Creates a new default instance.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function default(string $name, ?string $title = null): PivotField
    {
        return new PivotField($name, $title);
    }

    /**
     * Creates a new month instance.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function month(string $name, ?string $title = null): PivotMonthField
    {
        return new PivotMonthField($name, $title);
    }

    /**
     * Creates a new quarter field instance.
     *
     * @param string                 $name      the field name
     * @param ?string                $title     the field title
     * @param ?\Closure(int): string $formatter the optional callback formatter
     */
    public static function quarter(
        string $name,
        ?string $title = null,
        ?\Closure $formatter = null
    ): PivotQuarterField {
        return new PivotQuarterField($name, $title, $formatter);
    }

    /**
     * Creates a semester field instance.
     *
     * @param string                 $name      the field name
     * @param ?string                $title     the field title
     * @param ?\Closure(int): string $formatter the optional callback formatter
     */
    public static function semester(
        string $name,
        ?string $title = null,
        ?\Closure $formatter = null
    ): PivotSemesterField {
        return new PivotSemesterField($name, $title, $formatter);
    }

    /**
     * Creates a week number field instance.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function week(string $name, ?string $title = null): PivotDateField
    {
        return new PivotDateField($name, PivotDateField::PART_WEEK, $title);
    }

    /**
     * Creates a new week day field instance.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function weekday(string $name, ?string $title = null): PivotWeekdayField
    {
        return new PivotWeekdayField($name, $title);
    }

    /**
     * Creates a year field instance.
     *
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public static function year(string $name, ?string $title = null): PivotDateField
    {
        return new PivotDateField($name, PivotDateField::PART_YEAR, $title);
    }
}
