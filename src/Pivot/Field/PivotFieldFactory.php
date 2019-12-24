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
 * Factory to create PivotField.
 *
 * @author Laurent Muller
 */
class PivotFieldFactory
{
    /**
     * Creates a new instance with the METHOD_STRING.
     *
     * @param string $name   the field name
     * @param string $title  the field title
     * @param int    $method the field method
     */
    public static function default(string $name, ?string $title = null, int $method = PivotField::METHOD_STRING): PivotField
    {
        return (new PivotField($name, $title))->setMethod($method);
    }

    /**
     * Creates a new instance with the METHOD_FLOAT.
     *
     * @param string $name  the field name
     * @param string $title the field title
     */
    public static function float(string $name, ?string $title = null): PivotField
    {
        return self::default($name, $title, PivotField::METHOD_FLOAT);
    }

    /**
     * Creates a new instance with the METHOD_INTEGER.
     *
     * @param string $name  the field name
     * @param string $title the field title
     */
    public static function integer(string $name, ?string $title = null): PivotField
    {
        return self::default($name, $title, PivotField::METHOD_INTEGER);
    }

    /**
     * Creates a new month instance.
     *
     * @param string $name  the field name
     * @param bool   $short true to display the short month name, false to display the full month name
     */
    public static function month(string $name, bool $short = false): PivotMonthField
    {
        return new PivotMonthField($name, $short);
    }

    /**
     * Creates a new date instance for week number.
     *
     * @param string $name  the field name
     * @param string $title the field title
     */
    public static function week(string $name, ?string $title = null): PivotDateField
    {
        return new PivotDateField($name, PivotDateField::PART_WEEK, $title);
    }

    /**
     * Creates a new week day instance.
     *
     * @param string $name  the field name
     * @param bool   $short true to display the short day name, false to display the full day name
     */
    public static function weekday(string $name, bool $short = false): PivotWeekdayField
    {
        return new PivotWeekdayField($name, $short);
    }

    /**
     * Creates a new date instance for year.
     *
     * @param string $name  the field name
     * @param string $title the field title
     */
    public static function year(string $name, ?string $title = null): PivotDateField
    {
        return new PivotDateField($name, PivotDateField::PART_YEAR, $title);
    }
}
