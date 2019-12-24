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

namespace App\Utils;

use Locale;

/**
 * Utility class for dates.
 *
 * @author Laurent Muller
 *
 * @internal
 */
final class DateUtils
{
    /**
     * The month names.
     *
     * @var array
     */
    private static $monthNames;

    /**
     * The short month names.
     *
     * @var array
     */
    private static $shortMonthNames;

    /**
     * The short week day names.
     *
     * @var array
     */
    private static $shortWeekNames;

    /**
     * The week day names.
     *
     * @var array
     */
    private static $weekNames;

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Complete the give year with four digits.
     * For example, if year is set with 2 digits (10); the return value will be 2010.
     *
     * @param int $year   the year to complet
     * @param int $change the year change limit
     *
     * @return int the full year
     */
    public static function completYear(int $year, int $change = 1930): int
    {
        if ($year < 99) {
            return 100 + $change + ($year - $change) % 100;
        }

        return $year;
    }

//     /**
//      * Gets the first day of the month.
//      *
//      * @param int|null $year  the desired year or null for the current year
//      * @param int|null $month the desired month or null for the current month
//      *
//      * @return \DateTime
//      */
//     public static function firstDayOfMonth(?int $year = null, ?int $month = null): \DateTime
//     {
//         $year = $year ?: \gmdate('Y');
//         $month = $month ?: \gmdate('n');
//         $timestamp = \gmmktime(0, 0, 0, $month, 1, $year);
//         return new \DateTime("@{$timestamp}");
//     }
//     /**
//      * Gets the first day of the year.
//      *
//      * @param int|null $year the desired year or null for the current year
//      *
//      * @return \DateTime
//      */
//     public static function firstDayOfYear(?int $year = null): \DateTime
//     {
//         $year = $year ?: \gmdate('Y');
//         $timestamp = \gmmktime(0, 0, 0, 1, 1, $year);
//         return new \DateTime("@{$timestamp}");
//     }

    /**
     * Gets the localized month names.
     * For example with 'fr' as locale, return
     * <pre>
     * Janvier
     * Février
     * ...
     * </pre>.
     *
     * @return string[]
     */
    public static function getMonths(): array
    {
        if (!self::$monthNames) {
            self::$monthNames = self::getMonthNames('%B');
        }

        return self::$monthNames;
    }

    /**
     * Gets the localized short month names.
     * For example with 'fr' as locale, return
     * <pre>
     * Janv.
     * Févr.
     * ...
     * </pre>.
     *
     * @return string[]
     */
    public static function getShortMonths(): array
    {
        if (!self::$shortMonthNames) {
            self::$shortMonthNames = self::getMonthNames('%b');
        }

        return self::$shortMonthNames;
    }

    /**
     * Gets the localized short week day names.
     * For example with 'fr' as locale and 'sunday' as first day, return
     * <pre>
     * Dim.
     * Lun.
     * Mar.
     * ...
     * </pre>.
     *
     * @param string $firstday The first day of the week like 'sunday' or 'monday'
     *
     * @return string[]
     */
    public static function getShortWeekdays(?string $firstday = 'sunday'): array
    {
        if (!self::$shortWeekNames) {
            self::$shortWeekNames = self::getDayNames('%a', $firstday);
        }

        return self::$shortWeekNames;
    }

    /**
     * Gets the default time zone.
     */
    public static function getTimeZone(): string
    {
        return \date_default_timezone_get();
    }

    /**
     * Gets the localized week day names.
     * For example with 'fr' as locale and 'sunday' as first day, return
     * <pre>
     * Dimanche
     * Lundi
     * Mardi
     * ...
     * </pre>.
     *
     * @param string $firstday the first day of the week like 'sunday' or 'monday'
     *
     * @return string[]
     */
    public static function getWeekdays(?string $firstday = 'sunday'): array
    {
        if (!self::$weekNames) {
            self::$weekNames = self::getDayNames('%A', $firstday);
        }

        return self::$weekNames;
    }

//     /**
//      * Gets the last day of the year.
//      *
//      * @param int|null $year the desired year or null for the current year
//      *  @param int|null $month the desired month or null for the current month
//      *
//      * @return \DateTime
//      */
//     public static function lastDayOfMonth(?int $year = null, ?int $month = null): \DateTime
//     {
//         $year = $year ?: \gmdate('Y');
//         $month = $month ?: \gmdate('n');
//         $timestamp = \gmmktime(0, 0, 0, $month, 1, $year);
//         $date = new \DateTime("@{$timestamp}");

//         return $date->modify('+1 month')->modify('-1 day');
//     }
//     //

//     /**
//      * Gets the last day of the month.
//      *
//      * @param int|null $year the desired year or null for the current year
//      *
//      * @return \DateTime
//      */
//     public static function lastDayOfYear(?int $year = null): \DateTime
//     {
//         $year = $year ?: \gmdate('Y');
//         $timestamp = \gmmktime(0, 0, 0, 12, 31, $year);

//         return new \DateTime("@{$timestamp}");
//     }

    /**
     * Formats the given time.
     *
     * @param string $format the format
     * @param int    $time   the time
     *
     * @return string The formatted time
     */
    private static function format(string $format, int $time): string
    {
        self::setDefaultLocale();
        $name = \ucfirst(\strftime($format, $time));

        return \utf8_encode($name);
    }

    /**
     * Gets week day names.
     *
     * @param string $format   the date format
     * @param string $firstday the first day of the week like 'sunday' or 'monday'
     *
     * @return array the week day names
     */
    private static function getDayNames(string $format, $firstday = 'sunday'): array
    {
        $result = [];
        for ($i = 0; $i <= 6; ++$i) {
            $time = \strtotime("last {$firstday} +{$i} day");
            $result[$i + 1] = self::format($format, $time);
        }

        return $result;
    }

    /**
     * Gets the month names.
     *
     * @param string $format the date format
     *
     * @return array the month names
     */
    private static function getMonthNames(string $format): array
    {
        $result = [];
        for ($i = 1; $i <= 12; ++$i) {
            $time = \mktime(0, 0, 0, $i, 1);
            $result[$i] = self::format($format, $time);
        }

        return $result;
    }

    /**
     * Sets the default locale for time formats.
     *
     * @return bool true if set
     */
    private static function setDefaultLocale(): bool
    {
        $locale = Locale::getDefault();
        if (false === \setlocale(LC_TIME, $locale)) {
            $locale = \explode('_', $locale)[0];

            return false !== \setlocale(LC_TIME, $locale);
        }

        return true;
    }
}
