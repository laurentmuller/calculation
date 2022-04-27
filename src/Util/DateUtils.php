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

namespace App\Util;

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
     *  @var array<string, array<int, string>>
     */
    private static array $monthNames = [];

    /**
     * The short month names.
     *
     *  @var array<string, array<int, string>>
     */
    private static array $shortMonthNames = [];

    /**
     * The short week day names.
     *
     * @var array<string, array<int, string>>
     */
    private static array $shortWeekNames = [];

    /**
     * The week day names.
     *
     * @var array<string, array<int, string>>
     */
    private static array $weekNames = [];

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Returns a new date with the given interval added.
     *
     * @param \DateTimeInterface   $date     the date
     * @param \DateInterval|string $interval the interval to add
     *
     * @return \DateTimeInterface the new date
     */
    public static function add(\DateTimeInterface $date, \DateInterval|string $interval): \DateTimeInterface
    {
        if (\is_string($interval)) {
            $interval = new \DateInterval($interval);
        }
        /** @var \DateTime $clone */
        $clone = (clone $date);

        return $clone->add($interval);
    }

    /**
     * Complete the give year with four digits.
     *
     * For example, if year is set with 2 digits (10); the return value will be 2010.
     *
     * @param int $year   the year to complet
     * @param int $change the year change limit
     *
     * @return int the full year
     */
    public static function completYear(int $year, int $change = 1930): int
    {
        if ($year <= 99) {
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
     * @param string|null $locale The locale to format names or null to use default
     *
     * @return array<int, string>
     */
    public static function getMonths(?string $locale = null): array
    {
        $locale ??= \Locale::getDefault();
        if (empty(self::$monthNames[$locale])) {
            self::$monthNames[$locale] = self::getMonthNames('MMMM', $locale);
        }

        return self::$monthNames[$locale];
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
     * @param string|null $locale The locale to format names or null to use default
     *
     * @return array<int, string>
     */
    public static function getShortMonths(?string $locale = null): array
    {
        $locale ??= \Locale::getDefault();
        if (empty(self::$shortMonthNames[$locale])) {
            self::$shortMonthNames[$locale] = self::getMonthNames('MMM', $locale);
        }

        return self::$shortMonthNames[$locale];
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
     * @param string      $firstday The first day of the week, in english,  like 'sunday' or 'monday'
     * @param string|null $locale   The locale to format names or null to use default
     *
     * @return array<int, string>
     */
    public static function getShortWeekdays(string $firstday = 'sunday', ?string $locale = null): array
    {
        $locale ??= \Locale::getDefault();
        $key = "$firstday|$locale";
        if (empty(self::$shortWeekNames[$key])) {
            self::$shortWeekNames[$key] = self::getDayNames('eee', $firstday, $locale);
        }

        return self::$shortWeekNames[$key];
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
     * @param string      $firstday the first day of the week, in english, like 'sunday' or 'monday'
     * @param string|null $locale   The locale to format names or null to use default
     *
     * @return array<int, string>
     */
    public static function getWeekdays(string $firstday = 'sunday', ?string $locale = null): array
    {
        $locale ??= \Locale::getDefault();
        $key = "$firstday|$locale";
        if (empty(self::$weekNames[$key])) {
            self::$weekNames[$key] = self::getDayNames('eeee', $firstday, $locale);
        }

        return self::$weekNames[$key];
    }

    /**
     * Returns a new date with the given interval subtracted.
     *
     * @param \DateTimeInterface   $date     the date
     * @param \DateInterval|string $interval the interval to subtract
     *
     * @return \DateTimeInterface the new date
     */
    public static function sub(\DateTimeInterface $date, \DateInterval|string $interval): \DateTimeInterface
    {
        if (\is_string($interval)) {
            $interval = new \DateInterval($interval);
        }
        /** @var \DateTime $clone */
        $clone = (clone $date);

        return $clone->sub($interval);
    }

    /**
     * Gets week day names.
     *
     * @return array<int, string>
     */
    private static function getDayNames(string $pattern, string $firstday, string $locale): array
    {
        /** @var \IntlDateFormatter $formatter */
        $formatter = \IntlDateFormatter::create(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $result = [];
        for ($i = 0; $i <= 6; ++$i) {
            $time = (int) \strtotime("last $firstday + $i day");
            $value = (string) $formatter->format($time);
            $result[$i + 1] = \ucfirst($value);
        }

        return $result;
    }

    /**
     * Gets the month names.
     *
     * @return array<int, string>
     */
    private static function getMonthNames(string $pattern, string $locale): array
    {
        /** @var \IntlDateFormatter $formatter */
        $formatter = \IntlDateFormatter::create(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $result = [];
        $date = new \DateTime('2000-01-01');
        $interval = new \DateInterval('P1M');
        for ($i = 1; $i <= 12; ++$i) {
            $result[$i] = \ucfirst((string) $formatter->format($date));
            $date = $date->add($interval);
        }

        return $result;
    }
}
