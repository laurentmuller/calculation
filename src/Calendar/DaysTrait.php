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

/**
 * Trait to manage an array of days.
 *
 * @author Laurent Muller
 */
trait DaysTrait
{
    /**
     * The days.
     *
     * @var Day[]
     */
    protected $days = [];

    /**
     * Adds a day.
     */
    public function addDay(Day $day): self
    {
        $this->days[] = $day;

        return $this;
    }

    /**
     * Gets the day for the given key.
     *
     * @param int|\DateTimeInterface|string $key the day key. Can be an integer, a date interface or a formatted date ('d.m.Y').
     *
     * @return Day|null the day, if found, null otherwise
     */
    public function getDay($key): ?Day
    {
        if (\is_int($key)) {
            return \array_key_exists($key, $this->days) ? $this->days[$key] : null;
        }

        if ($key instanceof \DateTimeInterface) {
            $key = CalendarItem::getDateKey($key);
        }

        if (\is_string($key)) {
            foreach ($this->days as $day) {
                if ($day->getKey() === $key) {
                    return $day;
                }
            }
        }

        return null;
    }

    /**
     * Gets days.
     *
     * @return Day[]
     */
    public function getDays(): array
    {
        return $this->days;
    }

    /**
     * Gets the first date.
     */
    public function getFirstDate(): ?\DateTimeImmutable
    {
        $day = $this->getFirstDay();

        return $day ? $day->getDate() : null;
    }

    /**
     * Gets first day.
     */
    public function getFirstDay(): ?Day
    {
        if (\count($this->days)) {
            return \reset($this->days);
        }

        return null;
    }

    /**
     * Gets the last date.
     */
    public function getLastDate(): ?\DateTimeImmutable
    {
        $day = $this->getLastDay();

        return $day ? $day->getDate() : null;
    }

    /**
     * Gets the last day.
     */
    public function getLastDay(): ?Day
    {
        if (\count($this->days)) {
            return \end($this->days);
        }

        return null;
    }
}
