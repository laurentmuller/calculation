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
 *
 * @author Laurent Muller
 */
final class FormatExtension extends AbstractExtension
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Filter to format a boolean value.
     *
     * @param bool        $value     the value to format
     * @param string|null $true      the text to use when the value is <code>true</code> or <code>null</code> to use default
     * @param string|null $false     the text to use when the value is <code>false</code> or <code>null</code> to use default
     * @param bool        $translate <code>true</code> to translate texts
     */
    public function booleanFilter($value, ?string $true = null, ?string $false = null, bool $translate = false): string
    {
        if ((bool) $value) {
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
     * Formats a date for the current locale; ignoring the time part.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param string|null                    $dateFormat the date format
     * @param \DateTimeZone|string|null      $timezone   the time zone
     * @param string|null                    $calendar   the calendar type
     *
     * @return string the formatted date
     *
     * @throws SyntaxError if the date format or the time format is unknown
     */
    public function dateFilter(Environment $env, $date, ?string $dateFormat = null, $timezone = null, ?string $calendar = 'gregorian'): string
    {
        return $this->dateTimeFilter($env, $date, $dateFormat, 'none', $timezone, $calendar);
    }

    /**
     * Formats a date and time for the current locale.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param string|null                    $dateFormat the date format
     * @param string|null                    $timeFormat the time format
     * @param \DateTimeZone|string|null      $timezone   the time zone
     * @param string|null                    $calendar   the calendar type
     *
     * @throws SyntaxError if the date format or the time format is unknown
     *
     * @return string the formatted date
     */
    public function dateTimeFilter(Environment $env, $date, ?string $dateFormat = null, ?string $timeFormat = null, $timezone = null, ?string $calendar = 'gregorian'): string
    {
        static $formats = [
            'none' => \IntlDateFormatter::NONE,
            'short' => \IntlDateFormatter::SHORT,
            'medium' => \IntlDateFormatter::MEDIUM,
            'long' => \IntlDateFormatter::LONG,
            'full' => \IntlDateFormatter::FULL,
        ];

        // check formats
        if ($dateFormat && !isset($formats[$dateFormat])) {
            throw new SyntaxError(\sprintf('The date format "%s" does not exist. Known formats are: "%s"', $dateFormat, \implode('", "', \array_keys($formats))));
        }
        if ($timeFormat && !isset($formats[$timeFormat])) {
            throw new SyntaxError(\sprintf('The time format "%s" does not exist. Known formats are: "%s"', $timeFormat, \implode('", "', \array_keys($formats))));
        }

        // get types
        $datetype = $dateFormat ? $formats[$dateFormat] : null;
        $timetype = $timeFormat ? $formats[$timeFormat] : null;

        // no date and time format?
        if (\IntlDateFormatter::NONE === $datetype && \IntlDateFormatter::NONE === $timetype) {
            return '';
        }

        // convert
        $date = twig_date_converter($env, $date, $timezone);
        $calendar = 'gregorian' === $calendar ? \IntlDateFormatter::GREGORIAN : \IntlDateFormatter::TRADITIONAL;

        // format
        return FormatUtils::formatDateTime($date, $datetype, $timetype, $timezone, $calendar);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('identifier', [FormatUtils::class, 'formatId']),
            new TwigFilter('integer', [FormatUtils::class, 'formatInt']),
            new TwigFilter('amount', [FormatUtils::class, 'formatAmount']),
            new TwigFilter('percent', [FormatUtils::class, 'formatPercent']),

            new TwigFilter('boolean', [$this, 'booleanFilter']),
            new TwigFilter('localedate', [$this, 'dateFilter'], ['needs_environment' => true]),
            new TwigFilter('localetime', [$this, 'timeFilter'], ['needs_environment' => true]),
            new TwigFilter('localedatetime', [$this, 'dateTimeFilter'], ['needs_environment' => true]),
        ];
    }

    /**
     * Gets the translator.
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
     * @param string|null                    $timeFormat the time format
     * @param \DateTimeZone|string|null      $timezone   the time zone
     * @param string|null                    $calendar   the calendar type
     *
     * @throws SyntaxError if the date format or the time format is unknown
     *
     * @return string the formatted date
     */
    public function timeFilter(Environment $env, $date, ?string $timeFormat = null, $timezone = null, ?string $calendar = 'gregorian'): string
    {
        return $this->dateTimeFilter($env, $date, 'none', $timeFormat, $timezone, $calendar);
    }
}
