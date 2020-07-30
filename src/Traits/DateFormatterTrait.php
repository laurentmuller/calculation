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

namespace App\Traits;

use App\Util\FormatUtils;

/**
 * Trait to format localized dates and times.
 *
 * @author Laurent Muller
 *
 * @see \IntlDateFormatter
 */
trait DateFormatterTrait
{
    /**
     * Format a date for the current locale; ignoring the time part.
     *
     * @param \DateTime|int                           $date     the date to format
     * @param int|null                                $datetype the type of date formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting or parsing; default is Gregorian
     * @param string|null                             $pattern  Optional pattern to use when formatting
     *
     * @return string|bool the formatted date or false if formatting failed
     */
    public function localeDate($date, ?int $datetype = null, $timezone = null, ?int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null)
    {
        return $this->localeDateTime($date, $datetype, \IntlDateFormatter::NONE, $timezone, $calendar, $pattern);
    }

    /**
     * Format a date and time for the current locale.
     *
     * @param \DateTime|int                           $date     the date and time to format
     * @param int|null                                $datetype the type of date formatting, one of the format type constants or null to use default
     * @param int|null                                $timetype the type of time formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting or parsing; default is Gregorian
     * @param string|null                             $pattern  Optional pattern to use when formatting
     *
     * @return string|null the formatted date and time or null if formatting failed or if the date is null
     */
    public function localeDateTime($date, ?int $datetype = null, ?int $timetype = null, $timezone = null, ?int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null)
    {
        if ($date) {
            $formatter = $this->getDateFormatter($datetype, $timetype, $timezone, $calendar, $pattern);
            $result = $formatter->format($date);
            if (false !== $result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Format a time for the current locale; ignoring the date part.
     *
     * @param \DateTime|int                           $date     the time to format
     * @param int|null                                $timetype the type of date formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting or parsing; default is Gregorian
     * @param string|null                             $pattern  Optional pattern to use when formatting
     *
     * @return string|bool the formatted time or false if formatting failed
     */
    public function localeTime($date, ?int $timetype = null, $timezone = null, ?int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null)
    {
        return $this->localeDateTime($date, \IntlDateFormatter::NONE, $timetype, $timezone, $calendar, $pattern);
    }

    /**
     * Creates a date formatter.
     *
     * @param int|null                                $datetype the type of date formatting, one of the format type constants or null to use default
     * @param int|null                                $timetype the type of time formatting, one of the format type constants or null to use default
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone the timezone identifier
     * @param int                                     $calendar the calendar to use for formatting or parsing; default is Gregorian
     * @param string|null                             $pattern  Optional pattern to use when formatting
     *
     * @return \IntlDateFormatter the date formatter
     */
    protected function getDateFormatter(?int $datetype = null, ?int $timetype = null, $timezone = null, ?int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null): \IntlDateFormatter
    {
        // check values
        $pattern = $pattern ?: '';
        $datetype = $datetype ?: $this->getDefaultDateType();
        $timetype = $timetype ?: $this->getDefaultTimeType();

        // create formatter
        $formatter = \IntlDateFormatter::create(\Locale::getDefault(), $datetype, $timetype, $timezone, $calendar, $pattern);
        $formatter->setLenient(true);

        // check if year pattern is present within 4 digits
        $pattern = $formatter->getPattern();
        if (false === \strpos($pattern, 'yyyy') && false !== \strpos($pattern, 'yy')) {
            $pattern = \str_replace('yy', 'yyyy', $pattern);
            $formatter->setPattern($pattern);
        }

        return $formatter;
    }

    /**
     * Gets the default date type format.
     *
     * @return int type of date formatting, one of the format type constants
     */
    protected function getDefaultDateType(): int
    {
        return FormatUtils::getDateType();
    }

    /**
     * Gets the default time type format.
     *
     * @return int type of time formatting, one of the format type constants
     */
    protected function getDefaultTimeType(): int
    {
        return FormatUtils::getTimeType();
    }
}
