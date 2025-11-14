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

namespace App\Utils;

use Symfony\Component\Clock\DatePoint;

/**
 * Utility class for dates.
 *
 * @internal
 */
final class DateUtils
{
    /**
     * The month names.
     *
     * @var array<int, string>|null
     */
    private static ?array $monthNames = null;

    /**
     * The short month names.
     *
     * @var array<int, string>|null
     */
    private static ?array $shortMonthNames = null;

    /**
     * The short week day names.
     *
     * @var array<string, array<int, string>>|null
     */
    private static ?array $shortWeekNames = null;

    /**
     * The week day names.
     *
     * @var array<string, array<int, string>>|null
     */
    private static ?array $weekNames = null;

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Returns a new date point with the given interval added.
     *
     * @param DatePoint            $date     the date point
     * @param \DateInterval|string $interval the date interval to add
     */
    public static function add(DatePoint $date, \DateInterval|string $interval): DatePoint
    {
        if (\is_string($interval)) {
            $interval = self::createDateInterval($interval);
        }

        return $date->add($interval);
    }

    /**
     * Complete the given year with four digits.
     *
     * For example, if the year is set with 2 digits (10), the return value will be 2010.
     *
     * @param ?int $year   the year to complet or <code>null</code> to use the current year
     * @param int  $change the year change limit
     *
     * @return int the full year
     */
    public static function completYear(?int $year = null, int $change = 1930): int
    {
        if (null === $year) {
            return self::getYear();
        }
        if ($year <= 99) {
            return 100 + $change + ($year - $change) % 100;
        }

        return $year;
    }

    /**
     * Creates a new date point instance without the time part.
     *
     * @param string         $datetime a date/time string
     * @param ?\DateTimeZone $timezone the timezone or null to use the current timezone
     */
    public static function createDate(string $datetime = 'now', ?\DateTimeZone $timezone = null): DatePoint
    {
        return self::removeTime(self::createDatePoint($datetime, $timezone));
    }

    /**
     * Create a date interval.
     */
    public static function createDateInterval(string $interval): \DateInterval
    {
        return new \DateInterval($interval);
    }

    /**
     * Creates a new date point instance.
     *
     * @param string         $datetime a date/time string
     * @param ?\DateTimeZone $timezone the timezone or null to use the current timezone
     */
    public static function createDatePoint(string $datetime = 'now', ?\DateTimeZone $timezone = null): DatePoint
    {
        return new DatePoint($datetime, $timezone);
    }

    /**
     * Format the given date point (if any) to use within a date type in forms.
     */
    public static function formatFormDate(?DatePoint $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    /**
     * Gets the numeric representation of a day of the month for the given date point.
     *
     * @return int value 1 through 31
     */
    public static function getDay(DatePoint $date = new DatePoint()): int
    {
        return (int) $date->format('j');
    }

    /**
     * Gets the numeric representation of a month for the given date point.
     *
     * @return int value 1 through 12
     */
    public static function getMonth(DatePoint $date = new DatePoint()): int
    {
        return (int) $date->format('n');
    }

    /**
     * Gets the localized month names.
     * For example, with 'fr' as locale, return
     * <pre>
     * Janvier, Février, ...
     * </pre>.
     *
     * @return array<int, string>
     */
    public static function getMonths(): array
    {
        return self::$monthNames ??= self::getMonthNames('MMMM');
    }

    /**
     * Gets the localized short month names.
     * For example, with 'fr' as locale, return
     * <pre>
     * Janv., Févr., ...
     * </pre>.
     *
     * @return array<int, string>
     */
    public static function getShortMonths(): array
    {
        return self::$shortMonthNames ??= self::getMonthNames('MMM');
    }

    /**
     * Gets the localized short week day names.
     * For example, with 'fr' as locale and 'Sunday' as the first day, return
     * <pre>
     * Dim., Lun., Mar., Mer., Jeu., Ven., Sam., Dim.
     * </pre>.
     *
     * @param string $firstDay The first day of the week, in English, like 'Sunday' or 'Monday'
     *
     * @return array<int, string>
     */
    public static function getShortWeekdays(string $firstDay = 'monday'): array
    {
        return self::$shortWeekNames[$firstDay] ??= self::getDayNames('eee', $firstDay);
    }

    /**
     * Gets the ISO 8601 week number of year for the given date point.
     *
     * The weeks are starting on Monday.
     *
     * @param DatePoint $date the date to get week for
     *
     * @return int value 1 through 53
     */
    public static function getWeek(DatePoint $date = new DatePoint()): int
    {
        return (int) $date->format('W');
    }

    /**
     * Gets the localized week day names.
     * For example, with 'fr' as locale and 'sunday' as the first day, return
     * <pre>
     * Dimanche, Lundi, Mardi, ...
     * </pre>.
     *
     * @param string $firstDay the first day of the week, in English, like 'Sunday' or 'Monday'
     *
     * @return array<int, string>
     */
    public static function getWeekdays(string $firstDay = 'monday'): array
    {
        return self::$weekNames[$firstDay] ??= self::getDayNames('eeee', $firstDay);
    }

    /**
     * Gets the full numeric representation of a year with 4 digits for the given date point.
     *
     * @param DatePoint $date the date to get year for or <code>null</code> to use the current date
     */
    public static function getYear(DatePoint $date = new DatePoint()): int
    {
        return (int) $date->format('Y');
    }

    /**
     * Alters the timestamp of the given date point.
     *
     * @param DatePoint $date     the date to modify
     * @param string    $modifier a date/time string
     *
     * @return DatePoint the modified date
     */
    public static function modify(DatePoint $date, string $modifier): DatePoint
    {
        return $date->modify($modifier);
    }

    /**
     * Remove the time part of the given date point.
     */
    public static function removeTime(DatePoint $date = new DatePoint()): DatePoint
    {
        return $date->setTime(0, 0);
    }

    /**
     * Returns a new date point with the given interval subtracted.
     *
     * @param DatePoint            $date     the date point
     * @param \DateInterval|string $interval the date interval to subtract
     */
    public static function sub(DatePoint $date, \DateInterval|string $interval): DatePoint
    {
        if (\is_string($interval)) {
            $interval = self::createDateInterval($interval);
        }

        return $date->sub($interval);
    }

    /**
     * Convert the given date interface to a date point.
     */
    public static function toDatePoint(\DateTimeInterface $date): DatePoint
    {
        return $date instanceof DatePoint ? $date : DatePoint::createFromInterface($date);
    }

    /**
     * Gets week day names.
     *
     * @return array<int, string>
     */
    private static function getDayNames(string $pattern, string $firstDay): array
    {
        $result = [];
        $formatter = self::getFormatter($pattern);
        for ($i = 0; $i <= 6; ++$i) {
            $time = (int) \strtotime("last $firstDay + $i day");
            $result[$i + 1] = \ucfirst((string) $formatter->format($time));
        }

        return $result;
    }

    private static function getFormatter(string $pattern): \IntlDateFormatter
    {
        return FormatUtils::getDateFormatter(
            dateType: \IntlDateFormatter::NONE,
            timeType: \IntlDateFormatter::NONE,
            pattern: $pattern
        );
    }

    /**
     * @return array<int, string>
     */
    private static function getMonthNames(string $pattern): array
    {
        $result = [];
        $date = self::createDatePoint('2000-01-01');
        $interval = self::createDateInterval('P1M');
        $formatter = self::getFormatter($pattern);
        for ($i = 1; $i <= 12; ++$i) {
            $result[$i] = \ucfirst((string) $formatter->format($date));
            $date = $date->add($interval);
        }

        return $result;
    }
}
