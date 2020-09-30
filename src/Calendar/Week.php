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
 * Represents a week with a calendar and an array of days.
 *
 * @author Laurent Muller
 */
class Week extends CalendarItem
{
    use DaysTrait;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%d-%d)', $name, $this->getNumber(), $this->getYear());
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return CalendarItem::getWeekKey($this);
    }

    /**
     * Gets the months that this week is contained in.
     *
     * @return Month[]
     */
    public function getMonths(): array
    {
        $firstDate = $this->getFirstDate();
        $lastDate = $this->getLastDate();
        $callback = function (Month $month) use ($firstDate, $lastDate) {
            $monthFirst = $month->getFirstDate();
            if ($firstDate < $monthFirst && $lastDate < $monthFirst) {
                return false;
            }

            $monthLast = $month->getLastDate();
            if ($firstDate > $monthLast && $lastDate > $monthLast) {
                return false;
            }

            return true;
        };

        return \array_filter($this->calendar->getMonths(), $callback);
    }

    /**
     * {@inheritdoc}
     * This implementation returns the ISO-8601 week number of year for the last day of this week.
     * The weeks start on Monday (1 to 53).
     */
    public function getNumber(): int
    {
        /** @var \DateTimeImmutable $lastDate */
        $lastDate = $this->getLastDate();

        return (int) $lastDate->format('W');
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
        $today = $this->getToday();

        return $this->getYear() === $today->getYear()
            && $this->getNumber() === $today->getWeek();
    }

    /**
     * Returns if the given month is within this week.
     *
     * @param Month $month the month to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInMonth(Month $month): bool
    {
        /** @var Month[] $months */
        $months = $this->getMonths();
        foreach ($months as $current) {
            if ($current->getNumber() === $month->getNumber()
                && $current->getYear() === $month->getYear()) {
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
            'week' => $this->getNumber(),
            'startDate' => $this->localeDate($this->getFirstDate()),
            'endDate' => $this->localeDate($this->getLastDate()),
            'days' => $this->days,
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
