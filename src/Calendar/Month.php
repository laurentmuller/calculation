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
 * Represents a month with a calendar and an array of days.
 *
 * @author Laurent Muller
 */
class Month extends CalendarItem
{
    use DaysTrait;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%d.%d)', $name, $this->getNumber(), $this->getYear());
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return CalendarItem::getMonthKey($this);
    }

    /**
     * Gets this full name.
     */
    public function getName(): string
    {
        $names = $this->calendar->getMonthNames();

        return $names[$this->getNumber()];
    }

    /**
     * {@inheritdoc}
     *
     * This implementation returns the month number (1 to 12).
     */
    public function getNumber(): int
    {
        /** @var \DateTimeImmutable $firstDate */
        $firstDate = $this->getFirstDate();

        return (int) $firstDate->format('n');
    }

    /**
     * Gets this short name.
     */
    public function getShortName(): string
    {
        $names = $this->calendar->getMonthShortNames();

        return $names[$this->getNumber()];
    }

    /**
     * Gets the weeks that this month is contained in.
     *
     * @return Week[]
     */
    public function getWeeks(): array
    {
        $firstDate = $this->getFirstDate();
        $lastDate = $this->getLastDate();
        $callback = function (Week $week) use ($firstDate, $lastDate) {
            $weekFirst = $week->getFirstDate();
            if ($firstDate < $weekFirst && $lastDate < $weekFirst) {
                return false;
            }

            $weekLast = $week->getLastDate();
            if ($firstDate > $weekLast && $lastDate > $weekLast) {
                return false;
            }

            return true;
        };

        return \array_filter($this->calendar->getWeeks(), $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
        $today = $this->calendar->getToday();

        return $this->getYear() === $today->getYear()
            && $this->getNumber() === $today->getMonth();
    }

    /**
     * Returns if the given week is within this month.
     *
     * @param Week $week the week to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInWeek(Week $week): bool
    {
        /** @var Week[] $weeks */
        $weeks = $this->getWeeks();
        foreach ($weeks as $current) {
            if ($current->getNumber() === $week->getNumber()
                && $current->getYear() === $week->getYear()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'month' => $this->getNumber(),
            'name' => $this->getName(),
            'shortName' => $this->getShortName(),
            'startDate' => $this->localeDate($this->getFirstDate()),
            'endDate' => $this->localeDate($this->getLastDate()),
            'days' => $this->getDays(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function reset(): void
    {
        parent::reset();
        $this->days = [];
    }
}
