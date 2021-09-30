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

namespace App\Util;

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
     * @var string[]
     */
    private static ?array $monthNames = null;

    /**
     * The short month names.
     *
     * @var string[]
     */
    private static ?array $shortMonthNames = null;

    /**
     * The short week day names.
     *
     * @var string[]
     */
    private static ?array $shortWeekNames = null;

    /**
     * The week day names.
     *
     * @var string[]
     */
    private static ?array $weekNames = null;

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Retuns a new date with the given interval added.
     *
     * @param \DateTimeInterface   $date     the date
     * @param \DateInterval|string $interval the interval to add
     *
     * @return \DateTimeInterface the new date
     */
    public static function add(\DateTimeInterface $date, $interval): \DateTimeInterface
    {
        if (\is_string($interval)) {
            $interval = new \DateInterval($interval);
        }

        // @phpstan-ignore-next-line
        return (clone $date)->add($interval);
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
        if (null === self::$monthNames) {
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
        if (null === self::$shortMonthNames) {
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
    public static function getShortWeekdays(string $firstday = 'sunday'): array
    {
        if (null === self::$shortWeekNames) {
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
    public static function getWeekdays(string $firstday = 'sunday'): array
    {
        if (null === self::$weekNames) {
            self::$weekNames = self::getDayNames('%A', $firstday);
        }

        return self::$weekNames;
    }

    /**
     * Retuns a new date with the given interval subtracted.
     *
     * @param \DateTimeInterface   $date     the date
     * @param \DateInterval|string $interval the interval to subtract
     *
     * @return \DateTimeInterface the new date
     */
    public static function sub(\DateTimeInterface $date, $interval): \DateTimeInterface
    {
        if (\is_string($interval)) {
            $interval = new \DateInterval($interval);
        }

        // @phpstan-ignore-next-line
        return (clone $date)->sub($interval);
    }

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
        $name = \ucfirst((string) \strftime($format, $time));

        return \utf8_encode($name);
    }

    /**
     * Gets week day names.
     *
     * @param string $format   the date format
     * @param string $firstday the first day of the week like 'sunday' or 'monday'
     *
     * @return string[] the week day names
     */
    private static function getDayNames(string $format, string $firstday = 'sunday'): array
    {
        $result = [];
        for ($i = 0; $i <= 6; ++$i) {
            $time = \strtotime("last {$firstday} +{$i} day");
            $result[$i + 1] = self::format($format, (int) $time);
        }

        return $result;
    }

    /**
     * Gets the month names.
     *
     * @param string $format the date format
     *
     * @return string[] the month names
     */
    private static function getMonthNames(string $format): array
    {
        $result = [];
        for ($i = 1; $i <= 12; ++$i) {
            $time = \mktime(0, 0, 0, $i, 1);
            $result[$i] = self::format($format, (int) $time);
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
        $locale = \Locale::getDefault();
        if (false === \setlocale(\LC_TIME, $locale)) {
            return false !== \setlocale(\LC_TIME, \Locale::getPrimaryLanguage($locale));
        }

        return true;
    }
}
