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
     * @var array<int, string>
     */
    private static array $monthNames = [];

    /**
     * The short month names.
     *
     * @var array<int, string>
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
     *
     * @phpstan-template T of \DateTime|\DateTimeImmutable
     *
     * @phpstan-param T $date
     *
     * @phpstan-return T
     */
    public static function add(\DateTimeInterface $date, \DateInterval|string $interval): \DateTimeInterface
    {
        if (\is_string($interval)) {
            $interval = self::createDateInterval($interval);
        }
        if ($date instanceof \DateTime) {
            $date = (clone $date);
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

    public static function createDateInterval(string $interval): \DateInterval
    {
        return new \DateInterval($interval);
    }

    /**
     * Creates a new date time immutable instance.
     *
     * @param string         $datetime a date/time string
     * @param ?\DateTimeZone $timezone the timezone or null to use the current timezone
     */
    public static function createDateTime(string $datetime = 'now', ?\DateTimeZone $timezone = null): \DateTime
    {
        return new \DateTime($datetime, $timezone);
    }

    /**
     * Creates a new date time instance.
     *
     * @param string         $datetime a date/time string
     * @param ?\DateTimeZone $timezone the timezone or null to use the current timezone
     */
    public static function createDateTimeImmutable(string $datetime = 'now', ?\DateTimeZone $timezone = null): \DateTimeImmutable
    {
        return new \DateTimeImmutable($datetime, $timezone);
    }

    /**
     * Format the given date (if any) to use within a date type in forms.
     */
    public static function formatFormDate(?\DateTimeInterface $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    /**
     * Gets the numeric representation of a day of the month for the given date.
     *
     * @param ?\DateTimeInterface $date the date to get day for or <code>null</code> to use the current date
     *
     * @return int value 1 through 31
     */
    public static function getDay(?\DateTimeInterface $date = null): int
    {
        $date ??= self::createDateTime();

        return (int) $date->format('j');
    }

    /**
     * Gets the numeric representation of a month for the given date.
     *
     * @param ?\DateTimeInterface $date the date to get month for or <code>null</code> to use the current date
     *
     * @return int value 1 through 12
     */
    public static function getMonth(?\DateTimeInterface $date = null): int
    {
        $date ??= self::createDateTime();

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
        if ([] === self::$monthNames) {
            self::$monthNames = self::getMonthNames('MMMM');
        }

        return self::$monthNames;
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
        if ([] === self::$shortMonthNames) {
            self::$shortMonthNames = self::getMonthNames('MMM');
        }

        return self::$shortMonthNames;
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
        if (!isset(self::$shortWeekNames[$firstDay])) {
            self::$shortWeekNames[$firstDay] = self::getDayNames('eee', $firstDay);
        }

        return self::$shortWeekNames[$firstDay];
    }

    /**
     * Gets the ISO 8601 week number of year for the given date.
     *
     * The weeks are starting on Monday.
     *
     * @param ?\DateTimeInterface $date the date to get week for or <code>null</code> to use the current date
     *
     * @return int value 1 through 53
     */
    public static function getWeek(?\DateTimeInterface $date = null): int
    {
        $date ??= self::createDateTime();

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
        if (!isset(self::$weekNames[$firstDay])) {
            self::$weekNames[$firstDay] = self::getDayNames('eeee', $firstDay);
        }

        return self::$weekNames[$firstDay];
    }

    /**
     * Gets the full numeric representation of a year with 4 digits for the given date.
     *
     * @param ?\DateTimeInterface $date the date to get year for or <code>null</code> to use the current date
     */
    public static function getYear(?\DateTimeInterface $date = null): int
    {
        $date ??= self::createDateTime();

        return (int) $date->format('Y');
    }

    /**
     * Alters the timestamp of the given date.
     *
     * @param \DateTimeInterface $date     the date to modify
     * @param string             $modifier a date/time string
     *
     * @return \DateTimeInterface the modified date
     *
     * @phpstan-template T of \DateTime|\DateTimeImmutable
     *
     * @phpstan-param T $date
     *
     * @phpstan-return T
     */
    public static function modify(\DateTimeInterface $date, string $modifier): \DateTimeInterface
    {
        return (clone $date)->modify($modifier);
    }

    /**
     * Remove the time part of the given date.
     *
     * @phpstan-template T of \DateTime|\DateTimeImmutable
     *
     * @phpstan-param T|null $date
     *
     * @phpstan-return ($date is null ? \DateTime : T)
     */
    public static function removeTime(\DateTime|\DateTimeImmutable|null $date = null): \DateTime|\DateTimeImmutable
    {
        if (null !== $date) {
            /** @phpstan-var T $date */
            return $date->setTime(0, 0);
        }

        return self::createDateTime()->setTime(0, 0);
    }

    /**
     * Returns a new date with the given interval subtracted.
     *
     * @param \DateTimeInterface   $date     the date
     * @param \DateInterval|string $interval the date interval to subtract
     *
     * @return \DateTimeInterface the new date
     *
     * @phpstan-template T of \DateTime|\DateTimeImmutable
     *
     * @phpstan-param T $date
     *
     * @phpstan-return T
     */
    public static function sub(\DateTimeInterface $date, \DateInterval|string $interval): \DateTimeInterface
    {
        if (\is_string($interval)) {
            $interval = self::createDateInterval($interval);
        }

        if ($date instanceof \DateTime) {
            $date = (clone $date);
        }

        return $date->sub($interval);
    }

    /**
     * Convert the given date to a <code>\DateTimeImmutable</code>.
     */
    public static function toDateTimeImmutable(\DateTimeInterface $date): \DateTimeImmutable
    {
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        return \DateTimeImmutable::createFromInterface($date);
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
     * Gets the month names.
     *
     * @return array<int, string>
     */
    private static function getMonthNames(string $pattern): array
    {
        $result = [];
        $date = self::createDateTime('2000-01-01');
        $interval = self::createDateInterval('P1M');
        $formatter = self::getFormatter($pattern);
        for ($i = 1; $i <= 12; ++$i) {
            $result[$i] = \ucfirst((string) $formatter->format($date));
            $date = $date->add($interval);
        }

        return $result;
    }
}
