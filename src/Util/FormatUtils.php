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
 * Utility class for default formats.
 *
 * @author Laurent Muller
 */
final class FormatUtils
{
    /**
     * The Swiss french locale.
     */
    private const LOCALE_FR_CH = 'fr_CH';

    /**
     * Format a number for the current locale with 2 decimals (Ex: 2312.5 -> 2'312.50).
     *
     * @param float|int $number the value to format
     */
    public static function formatAmount($number): string
    {
        static $formatter;
        if (!$formatter) {
            $formatter = self::getNumberFormatter(\NumberFormatter::DECIMAL, 2);
        }

        return $formatter->format((float) $number);
    }

    /**
     * Format a date for the current locale; ignoring the time part.
     *
     * @param \DateTimeInterface|int|null             $date     the date to format
     * @param int|null                                $datetype the type of date formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting; default is Gregorian
     * @param string|null                             $pattern  the optional pattern to use when formatting
     *
     * @return string|null the formatted date or null if formatting failed or if the date is null
     */
    public static function formatDate($date, ?int $datetype = null, $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): ?string
    {
        return self::formatDateTime($date, $datetype, \IntlDateFormatter::NONE, $timezone, $calendar, $pattern);
    }

    /**
     * Format a date and time for the current locale.
     *
     * @param \DateTimeInterface|int|null             $date     the date and time to format
     * @param int|null                                $datetype the type of date formatting, one of the format type constants or null to use default
     * @param int|null                                $timetype the type of time formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting; default is Gregorian
     * @param string|null                             $pattern  the optional pattern to use when formatting
     *
     * @return string|null the formatted date and time or null if formatting failed or if the date is null
     */
    public static function formatDateTime($date, ?int $datetype = null, ?int $timetype = null, $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): ?string
    {
        if (null !== $date) {
            $formatter = self::getDateFormatter($datetype, $timetype, $timezone, $calendar, $pattern);
            $result = $formatter->format($date);
            if (false !== $result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Format an integer identifier with 0 left padding  (Ex: 123 -> 000123).
     *
     * @param int $number the value to format
     */
    public static function formatId($number): string
    {
        return \sprintf('%06d', (int) $number);
    }

    /**
     * Format a number for the current locale with 0 decimals (Ex: 2312.2 -> 2'312).
     *
     * @param float|int $number the value to format
     */
    public static function formatInt($number): string
    {
        static $formatter;
        if (!$formatter) {
            $formatter = self::getNumberFormatter(\NumberFormatter::DECIMAL, 0);
        }

        return $formatter->format((float) $number);
    }

    /**
     * Format for the current locale a number as percent.
     *
     * @param float $number      the value to format
     * @param bool  $includeSign true to include the percent sign
     * @param int   $decimals    the number of decimals
     */
    public static function formatPercent($number, bool $includeSign = true, int $decimals = 0): string
    {
        $formatter = self::getNumberFormatter(\NumberFormatter::PERCENT, $decimals);
        if (!$includeSign) {
            $formatter->setSymbol(\NumberFormatter::PERCENT_SYMBOL, '');
        }

        return $formatter->format((float) $number);
    }

    /**
     * Format a time for the current locale; ignoring the date part.
     *
     * @param \DateTimeInterface|int|null             $date     the time to format
     * @param int|null                                $timetype the type of date formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting; default is Gregorian
     * @param string|null                             $pattern  the optional pattern to use when formatting
     *
     * @return string|null the formatted time or null if formatting failed or if the date is null
     */
    public static function formatTime($date, ?int $timetype = null, $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): ?string
    {
        return self::formatDateTime($date, \IntlDateFormatter::NONE, $timetype, $timezone, $calendar, $pattern);
    }

    /**
     * Creates a date formatter for the current locale.
     *
     * @param int|null                                $datetype the type of date formatting, one of the format type constants or null to use default
     * @param int|null                                $timetype the type of time formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting; default is Gregorian
     * @param string|null                             $pattern  the optional pattern to use when formatting
     *
     * @return \IntlDateFormatter the date formatter
     */
    public static function getDateFormatter(?int $datetype = null, ?int $timetype = null, $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): \IntlDateFormatter
    {
        static $formatters = [];

        // check values
        $pattern ??= '';
        $locale = \Locale::getDefault();
        $datetype ??= self::getDateType();
        $timetype ??= self::getTimeType();

        $hash = $pattern . '|' . $locale . '|' . $datetype . '|' . $timetype . '|' . $calendar . '|' . self::hashTimeZone($timezone);
        if (!isset($formatters[$hash])) {
            /** @var \IntlDateFormatter $formatter */
            $formatter = \IntlDateFormatter::create($locale, $datetype, $timetype, $timezone, $calendar, $pattern);
            $formatter->setLenient(true);

            // check if year pattern is present within 4 digits
            $pattern = $formatter->getPattern();
            if (self::LOCALE_FR_CH === $locale && false === \strpos($pattern, 'yyyy') && false !== \strpos($pattern, 'yy')) {
                $pattern = \str_replace('yy', 'yyyy', $pattern);
                $formatter->setPattern($pattern);
            }

            $formatters[$hash] = $formatter;
        }

        return $formatters[$hash];
    }

    /**
     * Gets the default date type format.
     *
     * @return int type of date formatting, one of the format type constants
     */
    public static function getDateType(): int
    {
        return \IntlDateFormatter::SHORT;
    }

    /**
     * Gets the default decimal separator for the current locale.
     *
     * @return string the decimal separator
     */
    public static function getDecimal(): string
    {
        static $decimal;
        if ($decimal) {
            return $decimal;
        }

        // special case for Swiss French
        $locale = \Locale::getDefault();
        if (self::LOCALE_FR_CH === $locale) {
            $decimal = '.';
        } else {
            /** @var \NumberFormatter $formatter */
            $formatter = \NumberFormatter::create($locale, \NumberFormatter::DECIMAL);
            $decimal = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        }

        return $decimal;
    }

    /**
     * Gets the default grouping separator for the current locale.
     *
     * @return string the grouping separator
     */
    public static function getGrouping(): string
    {
        static $grouping;
        if ($grouping) {
            return $grouping;
        }

        // special case for Swiss French
        $locale = \Locale::getDefault();
        if (self::LOCALE_FR_CH === $locale) {
            $grouping = '\'';
        } else {
            /** @var \NumberFormatter $formatter */
            $formatter = \NumberFormatter::create($locale, \NumberFormatter::DECIMAL);
            $grouping = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        }

        // special case when space is in 2 characters
        if (2 === \strlen($grouping) && 194 === \ord($grouping[0]) && 160 === \ord($grouping[1])) {
            $grouping = ' ';
        }

        return $grouping;
    }

    /**
     * Gets a number formatter for the current locale.
     *
     * @param int $style  the style of the formatter
     * @param int $digits the number of fraction digits
     */
    public static function getNumberFormatter(int $style, int $digits): \NumberFormatter
    {
        static $formatters = [];

        $locale = \Locale::getDefault();
        $hash = $style . '|' . $digits . '|' . $locale;
        if (!isset($formatters[$hash])) {
            /** @var \NumberFormatter $formatter */
            $formatter = \NumberFormatter::create($locale, $style);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $digits);
            $formatter->setSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, self::getGrouping());
            $formatter->setSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, self::getDecimal());
            $formatters[$hash] = $formatter;
        }

        return $formatters[$hash];
    }

    /**
     * Gets the percent symbol for the current locale.
     *
     * @return string the percent symbol
     */
    public static function getPercent(): string
    {
        static $percent;
        if ($percent) {
            return $percent;
        }

        /** @var \NumberFormatter $formatter */
        $formatter = \NumberFormatter::create(\Locale::getDefault(), \NumberFormatter::PERCENT);
        $percent = $formatter->getSymbol(\NumberFormatter::PERCENT_SYMBOL);

        return $percent;
    }

    /**
     * Gets the default time type format.
     *
     * @return int type of time formatting, one of the format type constants
     */
    public static function getTimeType(): int
    {
        return \IntlDateFormatter::SHORT;
    }

    /**
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     */
    private static function hashTimeZone($timezone): string
    {
        if ($timezone instanceof \IntlTimeZone) {
            return $timezone->getID();
        } elseif ($timezone instanceof \DateTimeZone) {
            return $timezone->getName();
        } elseif (\is_string($timezone)) {
            return $timezone;
        } else {
            return '';
        }
    }
}
