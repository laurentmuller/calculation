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
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TwigFilter;

/**
 * Twig extension to format dates, numbers or boolean values.
 */
final class FormatExtension extends AbstractExtension
{
    use TranslatorTrait;

    private const DATE_FORMATS = [
        'none' => \IntlDateFormatter::NONE,
        'short' => \IntlDateFormatter::SHORT,
        'medium' => \IntlDateFormatter::MEDIUM,
        'long' => \IntlDateFormatter::LONG,
        'full' => \IntlDateFormatter::FULL,
    ];

    public function __construct(private readonly TranslatorInterface $translator)
    {
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

    public function getFilters(): array
    {
        $options = ['needs_environment' => true];

        return [
            new TwigFilter('identifier', FormatUtils::formatId(...)),
            new TwigFilter('integer', FormatUtils::formatInt(...)),
            new TwigFilter('amount', FormatUtils::formatAmount(...)),
            new TwigFilter('percent', FormatUtils::formatPercent(...)),
            new TwigFilter('boolean', $this->formatBoolean(...)),

            new TwigFilter('locale_datetime', $this->dateTimeFilter(...), $options),
            new TwigFilter('locale_date', $this->dateFilter(...), $options),
            new TwigFilter('locale_time', $this->timeFilter(...), $options),
        ];
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Formats a date for the current locale; ignoring the time part.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param string|null                    $dateFormat the date format
     * @param string|null                    $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @throws RuntimeError if the date format is invalid
     */
    private function dateFilter(
        Environment $env,
        \DateTimeInterface|string|null $date,
        ?string $dateFormat = null,
        ?string $pattern = null
    ): string {
        return $this->dateTimeFilter($env, $date, $dateFormat, 'none', $pattern);
    }

    /**
     * Formats a date and time for the current locale.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param string|null                    $dateFormat the date format
     * @param string|null                    $timeFormat the time format
     * @param string|null                    $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @throws RuntimeError if the date format or the time format is invalid
     *
     * @psalm-suppress InternalMethod
     */
    private function dateTimeFilter(
        Environment $env,
        \DateTimeInterface|string|null $date,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        ?string $pattern = null
    ): string {
        // check types
        $dateType = $this->translateFormat($dateFormat);
        $timeType = $this->translateFormat($timeFormat);
        if (\IntlDateFormatter::NONE === $dateType && \IntlDateFormatter::NONE === $timeType
                && !StringUtils::isString($pattern)) {
            return '';
        }

        /** @noinspection PhpInternalEntityUsedInspection */
        $date = CoreExtension::dateConverter($env, $date);

        return FormatUtils::formatDateTime($date, $dateType, $timeType, $pattern);
    }

    /**
     * Formats a time for the current locale; ignoring the date part.
     *
     * @param Environment                    $env        the Twig environment
     * @param \DateTimeInterface|string|null $date       the date
     * @param string|null                    $timeFormat the time format
     * @param string|null                    $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @throws RuntimeError if the time format is invalid
     */
    private function timeFilter(
        Environment $env,
        \DateTimeInterface|string|null $date,
        ?string $timeFormat = null,
        ?string $pattern = null
    ): string {
        return $this->dateTimeFilter($env, $date, 'none', $timeFormat, $pattern);
    }

    /**
     * Check the time format.
     *
     * @throws RuntimeError
     *
     * @psalm-return int<-1,3>|null
     */
    private function translateFormat(?string $format = null): ?int
    {
        if (null === $format || '' === $format) {
            return null;
        }
        if (!isset(self::DATE_FORMATS[$format])) {
            $formats = \implode('", "', \array_keys(self::DATE_FORMATS));
            $message = \sprintf('The date/time type "%s" does not exist. Allowed values are: "%s".', $format, $formats);
            throw new RuntimeError($message);
        }

        return self::DATE_FORMATS[$format];
    }
}
