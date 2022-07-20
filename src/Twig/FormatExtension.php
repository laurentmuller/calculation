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

namespace App\Twig;

use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to format dates, numbers or boolean values.
 */
final class FormatExtension extends AbstractExtension
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Formats a date for the current locale; ignoring the time part.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param ?string                        $dateFormat the date format
     * @param \DateTimeZone|string|null      $timezone   the time zone
     * @param ?string                        $calendar   the calendar type
     * @param ?string                        $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @throws SyntaxError if the date format or the time format is unknown
     */
    public function dateFilter(Environment $env, \DateTimeInterface|string|null $date, ?string $dateFormat = null, \DateTimeZone|string|null $timezone = null, ?string $calendar = 'gregorian', ?string $pattern = null): string
    {
        return $this->dateTimeFilter($env, $date, $dateFormat, 'none', $timezone, $calendar, $pattern);
    }

    /**
     * Formats a date and time for the current locale.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param ?string                        $dateFormat the date format
     * @param ?string                        $timeFormat the time format
     * @param \DateTimeZone|string|null      $timezone   the time zone
     * @param ?string                        $calendar   the calendar type
     * @param ?string                        $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @psalm-suppress UndefinedFunction
     *
     * @throws SyntaxError if the date format or the time format is unknown
     */
    public function dateTimeFilter(Environment $env, \DateTimeInterface|string|null $date, ?string $dateFormat = null, ?string $timeFormat = null, \DateTimeZone|string|null $timezone = null, ?string $calendar = 'gregorian', ?string $pattern = null): string
    {
        /** @psalm-var array<string, int> $formats */
        static $formats = [
            'none' => \IntlDateFormatter::NONE,
            'short' => \IntlDateFormatter::SHORT,
            'medium' => \IntlDateFormatter::MEDIUM,
            'long' => \IntlDateFormatter::LONG,
            'full' => \IntlDateFormatter::FULL,
        ];

        /** @psalm-var array<string, int> $calendars */
        static $calendars = [
            'gregorian' => \IntlDateFormatter::GREGORIAN,
            'traditional' => \IntlDateFormatter::TRADITIONAL,
        ];

        // check formats and calendar
        if (null !== $dateFormat && !isset($formats[$dateFormat])) {
            throw new SyntaxError(\sprintf('The date format "%s" does not exist. Known formats are: "%s"', $dateFormat, \implode('", "', \array_keys($formats))));
        }
        if (null !== $timeFormat && !isset($formats[$timeFormat])) {
            throw new SyntaxError(\sprintf('The time format "%s" does not exist. Known formats are: "%s"', $timeFormat, \implode('", "', \array_keys($formats))));
        }
        if (null !== $calendar && !isset($calendars[$calendar])) {
            throw new SyntaxError(\sprintf('The calendar "%s" does not exist. Known calendars are: "%s"', $calendar, \implode('", "', \array_keys($calendars))));
        }

        // get types and calendar
        $date_type = $dateFormat ? $formats[$dateFormat] : null;
        $time_type = $timeFormat ? $formats[$timeFormat] : null;
        $calendar = $calendars[$calendar ?? 'gregorian'];

        // no formats and pattern?
        if (\IntlDateFormatter::NONE === $date_type && \IntlDateFormatter::NONE === $time_type && null === $pattern) {
            return '';
        }

        // convert
        /** @psalm-var \DateTimeInterface $date */
        $date = twig_date_converter($env, $date, $timezone);

        // format
        return (string) FormatUtils::formatDateTime($date, $date_type, $time_type, $timezone, $calendar, $pattern);
    }

    /**
     * Filter to format a boolean value.
     *
     * @param bool    $value     the value to format
     * @param ?string $true      the text to use when the value is <code>true</code> or <code>null</code> to use default
     * @param ?string $false     the text to use when the value is <code>false</code> or <code>null</code> to use default
     * @param bool    $translate <code>true</code> to translate texts
     */
    public function formatBoolean(bool $value, ?string $true = null, ?string $false = null, bool $translate = false): string
    {
        if ($value) {
            if (null !== $true) {
                return $translate ? $this->trans($true) : $true;
            }

            return $this->trans('common.value_true');
        }

        if (null !== $false) {
            return $translate ? $this->trans($false) : $false;
        }

        return $this->trans('common.value_false');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        $options = ['needs_environment' => true];

        return [
            new TwigFilter('identifier', [FormatUtils::class, 'formatId']),
            new TwigFilter('integer', [FormatUtils::class, 'formatInt']),
            new TwigFilter('amount', [FormatUtils::class, 'formatAmount']),
            new TwigFilter('percent', [FormatUtils::class, 'formatPercent']),

            new TwigFilter('boolean', $this->formatBoolean(...)),
            new TwigFilter('locale_date', $this->dateFilter(...), $options),
            new TwigFilter('locale_time', $this->timeFilter(...), $options),
            new TwigFilter('locale_datetime', $this->dateTimeFilter(...), $options),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Formats a time for the current locale; ignoring the date part.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param ?string                        $timeFormat the time format
     * @param \DateTimeZone|string|null      $timezone   the time zone
     * @param ?string                        $calendar   the calendar type
     * @param ?string                        $pattern    the optional pattern to use when formatting
     *
     * @throws SyntaxError if the date format or the time format is unknown
     *
     * @return string the formatted date
     */
    public function timeFilter(Environment $env, \DateTimeInterface|string|null $date, ?string $timeFormat = null, \DateTimeZone|string|null $timezone = null, ?string $calendar = 'gregorian', ?string $pattern = null): string
    {
        return $this->dateTimeFilter($env, $date, 'none', $timeFormat, $timezone, $calendar, $pattern);
    }
}
