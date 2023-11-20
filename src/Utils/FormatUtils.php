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

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

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
     * @param ?int                                    $datetype the type of date formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param ?int                                    $calendar the calendar to use or null to use Gregorian calendar
     * @param ?string                                 $pattern  the optional pattern to use when formatting
     * @param ?string                                 $locale   the local or null to use default locale
     *
     * @return string|null the formatted date or null if formatting failed or if the date is null
     *
     * @psalm-return ($date is null ? (string|null) : string)
     */
    public static function formatDate(\DateTimeInterface|int|null $date, int $datetype = null, \IntlTimeZone|\DateTimeZone|string $timezone = null, int $calendar = null, string $pattern = null, string $locale = null): ?string
    {
        return self::formatDateTime($date, $datetype, \IntlDateFormatter::NONE, $timezone, $calendar, $pattern, $locale);
    }

    /**
     * Format a date and time for the current locale.
     *
     * @param \DateTimeInterface|int|null             $date     the date and time to format
     * @param ?int                                    $datetype the type of date formatting, one of the format type constants or null to use default
     * @param ?int                                    $timetype the type of time formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param ?int                                    $calendar the calendar to use or null to use Gregorian calendar
     * @param ?string                                 $pattern  the optional pattern to use when formatting
     * @param ?string                                 $locale   the local or null to use default locale
     *
     * @return string|null the formatted date and time or null if formatting failed or if the date is null
     *
     * @psalm-return ($date is null ? (string|null) : string)
     */
    public static function formatDateTime(\DateTimeInterface|int|null $date, int $datetype = null, int $timetype = null, \IntlTimeZone|\DateTimeZone|string $timezone = null, int $calendar = null, string $pattern = null, string $locale = null): ?string
    {
        if (null === $date) {
            return null;
        }

        $formatter = self::getDateFormatter($datetype, $timetype, $timezone, $calendar, $pattern, $locale);
        $result = $formatter->format($date);
        if (false === $result) {
            return null;
        }
        if (null !== $pattern && '' !== $pattern) {
            return \ucfirst($result);
        }

        return $result;
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
     * Format a number for the current locale with no decimal (Ex: 2312.2 -> 2'312).
     */
    public static function formatInt(\Countable|array|int|float|string|null $number): string
    {
        if ($number instanceof \Countable || \is_array($number)) {
            $number = \count($number);
        }
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
        $formatter = self::getNumberFormatter($style, $decimals, $roundingMode, $extraHash);
        if (!$includeSign) {
            $formatter->setSymbol(\NumberFormatter::PERCENT_SYMBOL, '');
        }
        $value = self::checkNegativeZero($number);

        return (string) $formatter->format($value);
    }

    /**
     * Format a time for the current locale; ignoring the date part.
     *
     * @param \DateTimeInterface|int|null             $date     the time to format
     * @param ?int                                    $timetype the type of date formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param ?int                                    $calendar the calendar to use or null to use Gregorian calendar
     * @param ?string                                 $pattern  the optional pattern to use when formatting
     * @param ?string                                 $locale   the local or null to use default locale
     *
     * @return string|null the formatted time or null if formatting failed or if the date is null
     *
     * @psalm-return ($date is null ? (string|null) : string)
     */
    public static function formatTime(\DateTimeInterface|int|null $date, int $timetype = null, \IntlTimeZone|\DateTimeZone|string $timezone = null, int $calendar = null, string $pattern = null, string $locale = null): ?string
    {
        return self::formatDateTime($date, \IntlDateFormatter::NONE, $timetype, $timezone, $calendar, $pattern, $locale);
    }

    /**
     * Creates a date formatter for the current locale.
     *
     * @param ?int                                    $datetype the type of date formatting, one of the format type constants or null to use default
     * @param ?int                                    $timetype the type of time formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param ?int                                    $calendar the calendar to use or null to use Gregorian calendar
     * @param ?string                                 $pattern  the optional pattern to use when formatting
     * @param ?string                                 $locale   the local or null to use default locale
     */
    public static function getDateFormatter(int $datetype = null, int $timetype = null, \IntlTimeZone|\DateTimeZone|string $timezone = null, int $calendar = null, string $pattern = null, string $locale = null): \IntlDateFormatter
    {
        // check values
        $datetype ??= self::getDateType();
        $timetype ??= self::getTimeType();
        $locale ??= \Locale::getDefault();
        $calendar ??= \IntlDateFormatter::GREGORIAN;
        $pattern ??= '';
        $hash = self::getHashCode($locale, $datetype, $timetype, $timezone, $calendar, $pattern);

        if (!isset(self::$dateFormatters[$hash])) {
            /** @var \IntlDateFormatter $formatter */
            $formatter = \IntlDateFormatter::create($locale, $datetype, $timetype, $timezone, $calendar, $pattern);
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
        $locale = \Locale::getDefault();
        if (self::LOCALE_FR_CH === $locale) {
            $decimal = '.';
        } else {
            /** @var \NumberFormatter $formatter */
            $formatter = \NumberFormatter::create($locale, \NumberFormatter::PATTERN_DECIMAL);
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
        $locale = \Locale::getDefault();
        if (self::LOCALE_FR_CH === $locale) {
            $grouping = '\'';
        } else {
            /** @var \NumberFormatter $formatter */
            $formatter = \NumberFormatter::create($locale, \NumberFormatter::PATTERN_DECIMAL);
            $grouping = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        }
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
        $hash = self::getHashCode($style, $digits, $roundingMode, $extraHash);
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
        $formatter = \NumberFormatter::create(\Locale::getDefault(), \NumberFormatter::PATTERN_DECIMAL);
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
    private static function checkNegativeZero(int|float|string|null $number): float
    {
        return empty($number) ? 0.0 : (float) $number;
    }

    private static function getHashCode(\IntlTimeZone|\DateTimeZone|string|int|null ...$values): string
    {
        $array = \array_map(function (\IntlTimeZone|\DateTimeZone|string|int|null $value): string {
            if ($value instanceof \IntlTimeZone) {
                return $value->getID();
            }
            if ($value instanceof \DateTimeZone) {
                return $value->getName();
            }

            return (string) $value;
        }, $values);

        return \implode('|', $array);
    }
}
