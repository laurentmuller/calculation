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
use Twig\Attribute\AsTwigFilter;

/**
 * Utility class for format dates and numbers.
 */
final class FormatUtils
{
    /**
     * The default date type format.
     */
    public const DATE_TYPE = \IntlDateFormatter::SHORT;

    /**
     * The decimal separator character.
     */
    public const DECIMAL_SEP = '.';

    /**
     * The default locale (Swiss French).
     */
    public const DEFAULT_LOCALE = 'fr_CH';

    /**
     * The default timezone (Europe/Zurich).
     */
    public const DEFAULT_TIME_ZONE = 'Europe/Zurich';

    /**
     * The fraction digits.
     */
    public const FRACTION_DIGITS = 2;

    /**
     * The percent symbol character.
     */
    public const PERCENT_SYMBOL = '%';

    /**
     * The thousand-separator character.
     */
    public const THOUSANDS_SEP = '\'';

    /**
     * The default time type format.
     */
    public const TIME_TYPE = \IntlDateFormatter::SHORT;

    /**
     * The date formatters cache.
     *
     * @var \IntlDateFormatter[]
     */
    private static array $dateFormatters = [];

    /**
     * The number formatters cache.
     *
     * @var \NumberFormatter[]
     */
    private static array $numberFormatters = [];

    /**
     * The formatter for a roman numeral.
     */
    private static ?\NumberFormatter $romanFormatter = null;

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Format a number for the current locale with 2 decimals (Ex: 2312.5 > 2'312.50).
     */
    #[AsTwigFilter(name: 'amount')]
    public static function formatAmount(float|int|string|null $number): string
    {
        $value = self::checkNegativeZero($number);

        return (string) self::getNumberFormatter(\NumberFormatter::DECIMAL, self::FRACTION_DIGITS)->format($value);
    }

    /**
     * Format a date for the current locale; ignoring the time part.
     *
     * @param DatePoint|int|null        $date     the date to format
     * @param int<-1,3>|null            $dateType the type of date formatting, one of the format types
     *                                            constants or null to use default
     * @param ?string                   $pattern  the optional pattern to use when formatting
     * @param \DateTimeZone|string|null $timezone the timezone identifier
     *
     * @return string|null the formatted date or null if formatting failed or if the date is null
     *
     * @phpstan-return ($date is null ? (string|null) : string)
     */
    public static function formatDate(
        DatePoint|int|null $date,
        ?int $dateType = null,
        ?string $pattern = null,
        \DateTimeZone|string|null $timezone = null
    ): ?string {
        return self::formatDateTime($date, $dateType, \IntlDateFormatter::NONE, $pattern, $timezone);
    }

    /**
     * Format a date and time for the current locale.
     *
     * @param DatePoint|int|null        $date     the date and time to format
     * @param int<-1,3>|null            $dateType the type of date formatting, one of the format types
     *                                            constants or null to use default
     * @param int<-1,3>|null            $timeType the type of time formatting, one of the format type constants or
     *                                            null to use default
     * @param ?string                   $pattern  the optional pattern to use when formatting
     * @param \DateTimeZone|string|null $timezone the timezone identifier
     *
     * @return string|null the formatted date and time or null if formatting failed or if the date is null
     *
     * @phpstan-return ($date is null ? (string|null) : string)
     */
    public static function formatDateTime(
        DatePoint|int|null $date,
        ?int $dateType = null,
        ?int $timeType = null,
        ?string $pattern = null,
        \DateTimeZone|string|null $timezone = null
    ): ?string {
        if (null === $date) {
            return null;
        }

        $formatter = self::getDateFormatter($dateType, $timeType, $pattern, $timezone);
        $result = (string) $formatter->format($date);
        if (StringUtils::isString($pattern)) {
            return \ucfirst($result);
        }

        return $result;
    }

    /**
     * Format an integer identifier with 0 left paddings (Ex: 123 > 000123).
     */
    #[AsTwigFilter(name: 'identifier')]
    public static function formatId(float|int|string|null $number): string
    {
        return \sprintf('%06d', self::checkNegativeZero($number));
    }

    /**
     * Format a number for the current locale with no decimal (Ex: 2312.2 > 2'312).
     *
     * @psalm-suppress PossiblyInvalidArgument
     */
    #[AsTwigFilter(name: 'integer')]
    public static function formatInt(\Countable|array|int|float|string|null $number): string
    {
        $value = self::checkNegativeZero(\is_countable($number) ? \count($number) : $number);

        return (string) self::getNumberFormatter(\NumberFormatter::DECIMAL, 0)->format($value);
    }

    /**
     * Format for the current locale a number with percent.
     *
     * @param float|int|string|null $number       the value to format
     * @param bool                  $includeSign  true to include the percent sign
     * @param int                   $decimals     the number of decimals
     * @param int                   $roundingMode the rounding mode
     *
     * @phpstan-param \NumberFormatter::ROUND_* $roundingMode
     */
    #[AsTwigFilter(name: 'percent')]
    public static function formatPercent(
        float|int|string|null $number,
        bool $includeSign = true,
        int $decimals = 0,
        int $roundingMode = \NumberFormatter::ROUND_DOWN
    ): string {
        $symbol = $includeSign ? self::PERCENT_SYMBOL : '';
        $formatter = self::getNumberFormatter(\NumberFormatter::PERCENT, $decimals, $roundingMode, $symbol);
        $value = self::checkNegativeZero($number);

        return (string) $formatter->format($value);
    }

    /**
     * Format the given value to a roman numeral.
     *
     * <b>N.B.:</b> Returns an empty string if the number is smaller than 1 or is greater than 3999.
     */
    public static function formatRoman(float|int|string|null $number): string
    {
        $value = (int) $number;
        if ($value < 1 || $value > 3999) {
            return '';
        }

        return (string) self::getRomanFormatter()->format($value);
    }

    /**
     * Format a time for the current locale; ignoring the date part.
     *
     * @param DatePoint|int|null        $date     the time to format
     * @param int<-1,3>|null            $timeType the type of date formatting, one of the format types
     *                                            constants or null to use default
     * @param ?string                   $pattern  the optional pattern to use when formatting
     * @param \DateTimeZone|string|null $timezone the timezone identifier
     *
     * @return string|null the formatted time or null if formatting failed or if the date is null
     *
     * @phpstan-return ($date is null ? (string|null) : string)
     */
    public static function formatTime(
        DatePoint|int|null $date,
        ?int $timeType = null,
        ?string $pattern = null,
        \DateTimeZone|string|null $timezone = null,
    ): ?string {
        return self::formatDateTime($date, \IntlDateFormatter::NONE, $timeType, $pattern, $timezone);
    }

    /**
     * Creates a date formatter for the current locale.
     *
     * @param int<-1,3>|null            $dateType the type of date formatting, one of the format types
     *                                            constants or null to use default
     * @param int<-1,3>|null            $timeType the type of time formatting, one of the format types constants or
     *                                            null to use default
     * @param ?string                   $pattern  the optional pattern to use when formatting
     * @param \DateTimeZone|string|null $timezone the timezone identifier
     */
    public static function getDateFormatter(
        ?int $dateType = null,
        ?int $timeType = null,
        ?string $pattern = null,
        \DateTimeZone|string|null $timezone = null
    ): \IntlDateFormatter {
        $locale = self::DEFAULT_LOCALE;
        $dateType ??= self::DATE_TYPE;
        $timeType ??= self::TIME_TYPE;
        $pattern ??= '';
        $hash = self::hashCode($dateType, $timeType, $timezone, $pattern);
        if (!isset(self::$dateFormatters[$hash])) {
            $formatter = new \IntlDateFormatter($locale, $dateType, $timeType, $timezone, pattern: $pattern);
            $pattern = (string) $formatter->getPattern();
            if (!\str_contains($pattern, 'yyyy') && \str_contains($pattern, 'yy')) {
                $pattern = \str_replace('yy', 'yyyy', $pattern);
                $formatter->setPattern($pattern);
            }
            self::$dateFormatters[$hash] = $formatter;
        }

        return self::$dateFormatters[$hash];
    }

    /**
     * Gets a number formatter for the current locale.
     *
     * @param int    $style         the style of the formatter
     * @param int    $digits        the number of fraction digits
     * @param int    $roundingMode  the rounding mode
     * @param string $percentSymbol an optional percent symbol
     *
     * @phpstan-param \NumberFormatter::ROUND_* $roundingMode
     */
    public static function getNumberFormatter(
        int $style,
        int $digits,
        int $roundingMode = \NumberFormatter::ROUND_HALFEVEN,
        string $percentSymbol = ''
    ): \NumberFormatter {
        $hash = self::hashCode($style, $digits, $roundingMode, $percentSymbol);
        if (!isset(self::$numberFormatters[$hash])) {
            $formatter = new \NumberFormatter(self::DEFAULT_LOCALE, $style);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $digits);
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $roundingMode);
            $formatter->setSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, self::THOUSANDS_SEP);
            $formatter->setSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, self::DECIMAL_SEP);
            $formatter->setSymbol(\NumberFormatter::PERCENT_SYMBOL, $percentSymbol);
            self::$numberFormatters[$hash] = $formatter;
        }

        return self::$numberFormatters[$hash];
    }

    /**
     * Gets the formatter used format values to a roman numeral.
     */
    public static function getRomanFormatter(): \NumberFormatter
    {
        return self::$romanFormatter ??= new \NumberFormatter('@numbers=roman', \NumberFormatter::DECIMAL);
    }

    private static function checkNegativeZero(int|float|string|null $number): float
    {
        $value = (float) $number;

        return ($value ** -1.0) === -\INF ? 0.0 : $value;
    }

    private static function hashCode(\DateTimeZone|string|int|null ...$values): string
    {
        $values = \array_map(
            static fn (mixed $value): string => $value instanceof \DateTimeZone ? $value->getName() : (string) $value,
            $values
        );

        return \implode('|', $values);
    }
}
