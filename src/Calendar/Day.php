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

namespace App\Calendar;

use App\Util\Utils;

/**
 * Represents a single day with a date.
 *
 * @author Laurent Muller
 */
class Day extends CalendarItem implements WeekDaysInterface
{
    /**
     * The date.
     *
     * @var \DateTimeImmutable
     */
    protected $date;

    /**
     * Constructor.
     *
     * @param Calendar  $calendar the parent calendar
     * @param \DateTime $date     the date
     */
    public function __construct(Calendar $calendar, \DateTime $date)
    {
        parent::__construct($calendar);
        $this->date = \DateTimeImmutable::createFromMutable($date);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%s)', $name, $this->localeDate($this->date));
    }

    /**
     * Returns this date formatted according to given format.
     *
     * @param string $format a format accepted by date
     *
     * @return string|bool the formatted date string on success or false on failure
     */
    public function format(string $format)
    {
        return $this->date->format($format);
    }

    /**
     * Gets the date.
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return CalendarItem::getDayKey($this);
    }

    /**
     * Gets the month number (1 to 12).
     */
    public function getMonth(): int
    {
        return (int) $this->format('n');
    }

    /**
     * Gets this full name.
     */
    public function getName(): string
    {
        $names = $this->calendar->getWeekNames();

        return $names[$this->getWeekDay()];
    }

    /**
     * This implementation returns the day of the month (1 to 31).
     */
    public function getNumber(): int
    {
        return (int) $this->format('j');
    }

    /**
     * Gets this short name.
     */
    public function getShortName(): string
    {
        $names = $this->calendar->getWeekShortNames();

        return $names[$this->getWeekDay()];
    }

    /**
     * Gets the Unix timestamp. This is a shortcut for:
     * <pre>
     * <code>
     * $day->getDate()->getTimestamp();
     * </code>
     * </pre>.
     *
     * @return int the Unix timestamp representing this date
     */
    public function getTimestamp(): int
    {
        return $this->date->getTimestamp();
    }

    /**
     * Gets ISO-8601 week number of year, weeks starting on Monday.
     *
     * @return int 1 to 53
     */
    public function getWeek(): int
    {
        return (int) $this->format('W');
    }

    /**
     * Gets ISO-8601 numeric representation of the day of the week.
     *
     * @return int 1 (for Monday) through 7 (for Sunday)
     */
    public function getWeekDay(): int
    {
        return (int) $this->format('N');
    }

    /**
     * {@inheritdoc}
     */
    public function getYear(): int
    {
        return (int) $this->format('Y');
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
        $today = $this->getToday();

        return $this->getYear() === $today->getYear()
            && $this->getMonth() === $today->getMonth()
            && $this->getNumber() === $today->getNumber();
    }

    /**
     * Returns if this is the first day of the week (monday).
     */
    public function isFirstInWeek(): bool
    {
        return self::MONDAY === $this->getWeekDay();
    }

    /**
     * Returns if this day in within the given month.
     *
     * @param Month $month the month to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInMonth(Month $month): bool
    {
        return ($this->getMonth() === $month->getNumber())
            && ($this->getYear() === $month->getYear());
    }

    /**
     * Returns if this day in within the given week.
     *
     * @param Week $week the week to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInWeek(Week $week): bool
    {
        return  ($this->getWeek() === $week->getNumber())
            && ($this->getYear() === $week->getYear());
    }

    /**
     * Returns if this day in within the given year.
     *
     * @param int $year the year to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInYear(int $year): bool
    {
        return $this->getYear() === $year;
    }

    /**
     * Returns if this is the last day of the week (sunnday).
     */
    public function isLastInWeek(): bool
    {
        return self::SUNNDAY === $this->getWeekDay();
    }

    /**
     * Returns if this day is in the week-end (saturday or sunnday).
     */
    public function isWeekend(): bool
    {
        return \in_array($this->getWeekDay(), [self::SATURDAY, self::SUNNDAY], true);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'day' => $this->getNumber(),
            'name' => $this->getName(),
            'shortName' => $this->getShortName(),
            'date' => $this->localeDate($this->date),
        ];
    }
}
