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
 * Utility class for default formats.
 */
final class FormatUtils
{
    /**
     * The Swiss French locale.
     */
    private const LOCALE_FR_CH = 'fr_CH';

    /**
     * The date formatters.
     *
     * @var \IntlDateFormatter[]
     */
    private static array $dateFormatters = [];

    /**
     * The number formatters.
     *
     * @var \NumberFormatter[]
     */
    private static array $numberFormatters = [];

    /**
     * Format a number for the current locale with 2 decimals (Ex: 2312.5 -> 2'312.50).
     */
    public static function formatAmount(float|int|string|null $number): string
    {
        $value = self::checkNegativeZero($number);

        return (string) self::getNumberFormatter(\NumberFormatter::DECIMAL, 2)->format($value);
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
    public static function formatDate(\DateTimeInterface|int|null $date, ?int $datetype = null, \IntlTimeZone|\DateTimeZone|string|null $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): ?string
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
    public static function formatDateTime(\DateTimeInterface|int|null $date, ?int $datetype = null, ?int $timetype = null, \IntlTimeZone|\DateTimeZone|string|null $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): ?string
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
     */
    public static function formatId(float|int|string|null $number): string
    {
        $value = self::checkNegativeZero($number);

        return \sprintf('%06d', $value);
    }

    /**
     * Format a number for the current locale with 2 decimals (Ex: 2312.2 -> 2'312.00).
     */
    public static function formatInt(float|int|string|null $number): string
    {
        $value = self::checkNegativeZero($number);

        return (string) self::getNumberFormatter(\NumberFormatter::DECIMAL, 0)->format($value);
    }

    /**
     * Format for the current locale a number with percent.
     *
     * @param float|int|string|null $number       the value to format
     * @param bool                  $includeSign  true to include the percent sign
     * @param int                   $decimals     the number of decimals
     * @param int                   $roundingMode the rounding mode
     */
    public static function formatPercent(float|int|string|null $number, bool $includeSign = true, int $decimals = 0, int $roundingMode = \NumberFormatter::ROUND_DOWN): string
    {
        $style = \NumberFormatter::PERCENT;
        $extraHash = $includeSign ? '1' : '0';
        $hash = self::getNumberHash($style, $decimals, $roundingMode, $extraHash);

        if (isset(self::$numberFormatters[$hash])) {
            $formatter = self::$numberFormatters[$hash];
        } else {
            $formatter = self::getNumberFormatter($style, $decimals, $roundingMode, $extraHash);
            if (!$includeSign) {
                $formatter->setSymbol(\NumberFormatter::PERCENT_SYMBOL, '');
            }
        }
        $value = self::checkNegativeZero($number);

        return (string) $formatter->format($value);
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
    public static function formatTime(\DateTimeInterface|int|null $date, ?int $timetype = null, \IntlTimeZone|\DateTimeZone|string|null $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): ?string
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
     */
    public static function getDateFormatter(?int $datetype = null, ?int $timetype = null, \IntlTimeZone|\DateTimeZone|string|null $timezone = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): \IntlDateFormatter
    {
        // check values
        $pattern ??= '';
        $locale = \Locale::getDefault();
        $datetype ??= self::getDateType();
        $timetype ??= self::getTimeType();

        $hash = $locale . '|' . $datetype . '|' . $timetype . '|' . self::hashTimeZone($timezone) . '|' . $calendar . '|' . $pattern;
        if (!isset(self::$dateFormatters[$hash])) {
            /** @var \IntlDateFormatter $formatter */
            $formatter = \IntlDateFormatter::create($locale, $datetype, $timetype, $timezone, $calendar, $pattern);

            // check if year pattern is present within 4 digits
            $pattern = $formatter->getPattern();
            if (self::LOCALE_FR_CH === $locale && !\str_contains($pattern, 'yyyy') && \str_contains($pattern, 'yy')) {
                $pattern = \str_replace('yy', 'yyyy', $pattern);
                $formatter->setPattern($pattern);
            }

            self::$dateFormatters[$hash] = $formatter;
        }

        return self::$dateFormatters[$hash];
    }

    /**
     * Gets the default date type format.
     */
    public static function getDateType(): int
    {
        return \IntlDateFormatter::SHORT;
    }

    /**
     * Gets the default decimal separator for the current locale.
     */
    public static function getDecimal(): string
    {
        /** @var string|null $decimal */
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
     */
    public static function getGrouping(): string
    {
        /** @var string|null $grouping */
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
     * @param int    $style        the style of the formatter
     * @param int    $digits       the number of fraction digits
     * @param int    $roundingMode the rounding mode
     * @param string $extraHash    an optional extra hash code used to check if the formatter is already created
     */
    public static function getNumberFormatter(int $style, int $digits, int $roundingMode = \NumberFormatter::ROUND_HALFEVEN, string $extraHash = ''): \NumberFormatter
    {
        $hash = self::getNumberHash($style, $digits, $roundingMode, $extraHash);
        if (!isset(self::$numberFormatters[$hash])) {
            /** @var \NumberFormatter $formatter */
            $formatter = \NumberFormatter::create(\Locale::getDefault(), $style);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $digits);
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $roundingMode);
            $formatter->setSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, self::getGrouping());
            $formatter->setSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, self::getDecimal());
            self::$numberFormatters[$hash] = $formatter;
        }

        return self::$numberFormatters[$hash];
    }

    /**
     * Gets the percent symbol for the current locale.
     */
    public static function getPercent(): string
    {
        /** @var string|null $percent */
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
     */
    public static function getTimeType(): int
    {
        return \IntlDateFormatter::SHORT;
    }

    /**
     * Check the given value.
     */
    private static function checkNegativeZero(float|int|string|null $number): float
    {
        return empty($number) ? 0.0 : (float) $number;
    }

    /**
     * Gets a number formatter hash code for the current locale.
     */
    private static function getNumberHash(int $style, int $digits, int $roundingMode, string $extraHash = ''): string
    {
        return \Locale::getDefault() . '|' . $style . '|' . $digits . '|' . $roundingMode . '|' . $extraHash;
    }

    /**
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     */
    private static function hashTimeZone(\IntlTimeZone|\DateTimeZone|string|null $timezone): string
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
