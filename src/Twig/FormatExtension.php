<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Twig;

use App\Service\ApplicationService;
use App\Traits\FormatterTrait;
use App\Traits\TranslatorTrait;
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
    use FormatterTrait;
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, ApplicationService $application)
    {
        $this->translator = $translator;
        $this->application = $application;
    }

    /**
     * Filter to format a boolean value.
     *
     * @param bool   $value     the value to format
     * @param string $true      the text to use when the value is <code>true</code> or <code>null</code> to use default
     * @param string $false     the text to use when the value is <code>false</code> or <code>null</code> to use default
     * @param bool   $translate <code>true</code> to translate texts
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
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('identifier', [$this, 'localeId']),
            new TwigFilter('integer', [$this, 'localeInt']),
            new TwigFilter('amount', [$this, 'localeAmount']),
            new TwigFilter('boolean', [$this, 'booleanFilter']),
            new TwigFilter('percent', [$this, 'localePercent']),
            new TwigFilter('localedate', [$this, 'localeDateFilter'], ['needs_environment' => true]),
            new TwigFilter('localetime', [$this, 'localeTimeFilter'], ['needs_environment' => true]),
            new TwigFilter('localedatetime', [$this, 'localeDateTimeFilter'], ['needs_environment' => true]),
        ];
    }

    /**
     * Formats a date for the current locale; ignoring the time part.
     *
     * @param Environment               $env        the Twig environment
     * @param \DateTime|int             $date       the date
     * @param string|null               $dateFormat the date format
     * @param \DateTimeZone|string|null $timezone   the time zone
     * @param string|null               $calendar   the calendar type
     *
     * @throws SyntaxError if the date format or the time format is unknown
     *
     * @return string the formatted date
     */
    public function localeDateFilter(Environment $env, $date, ?string $dateFormat = null, $timezone = null, ?string $calendar = 'gregorian'): string
    {
        return $this->localeDateTimeFilter($env, $date, $dateFormat, 'none', $timezone, $calendar);
    }

    /**
     * Formats a date and time for the current locale.
     *
     * @param Environment               $env        the Twig environment
     * @param \DateTime|int             $date       the date
     * @param string|null               $dateFormat the date format
     * @param string|null               $timeFormat the time format
     * @param \DateTimeZone|string|null $timezone   the time zone
     * @param string|null               $calendar   the calendar type
     *
     * @throws SyntaxError if the date format or the time format is unknown
     *
     * @return string the formatted date
     */
    public function localeDateTimeFilter(Environment $env, $date, ?string $dateFormat = null, ?string $timeFormat = null, $timezone = null, ?string $calendar = 'gregorian'): string
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
        if (\IntlDateFormatter::NONE === $datetype && \IntlDateFormatter::NONE === $timetype) {
            return '';
        }

        // convert
        $date = twig_date_converter($env, $date, $timezone);
        $calendar = 'gregorian' === $calendar ? \IntlDateFormatter::GREGORIAN : \IntlDateFormatter::TRADITIONAL;

        // format
        return $this->localeDateTime($date, $datetype, $timetype, $timezone, $calendar);
    }

    /**
     * Formats a time for the current locale; ignoring the date part.
     *
     * @param Environment               $env        the Twig environment
     * @param \DateTime|int             $date       the date
     * @param string|null               $timeFormat the time format
     * @param \DateTimeZone|string|null $timezone   the time zone
     * @param string|null               $calendar   the calendar type
     *
     * @throws SyntaxError if the date format or the time format is unknown
     *
     * @return string the formatted date
     */
    public function localeTimeFilter(Environment $env, $date, ?string $timeFormat = null, $timezone = null, ?string $calendar = 'gregorian'): string
    {
        return $this->localeDateTimeFilter($env, $date, 'none', $timeFormat, $timezone, $calendar);
    }
}
