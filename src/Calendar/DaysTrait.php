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
        $this->days[$day->getDayOfYear()] = $day;

        return $this;
    }

    /**
     * Gets the day for the given key.
     *
     * @param int|\DateTimeInterface|string $key the day key. Can be an integer, a date time interface or a formatted date ('d.m.Y').
     *
     * @return Day|null the day, if found, null otherwise
     */
    public function getDay($key): ?Day
    {
        if (\is_int($key)) {
            return $this->days[$key] ?? null;
        }

        if ($key instanceof \DateTimeInterface) {
            // find within the day of year
            $dayOfYear = (int) $key->format('z');
            if (\array_key_exists($dayOfYear, $this->days)) {
                return $this->days[$dayOfYear];
            }

            // formatted key
            $key = $key->format(Day::KEY_FORMAT);
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
     * Gets the first date or null if empty.
     */
    public function getFirstDate(): ?\DateTimeImmutable
    {
        if ($day = $this->getFirstDay()) {
            return $day->getDate();
        }

        return null;
    }

    /**
     * Gets first day or null if empty.
     */
    public function getFirstDay(): ?Day
    {
        return empty($this->days) ? null : \reset($this->days);
    }

    /**
     * Gets the last date or null if empty.
     */
    public function getLastDate(): ?\DateTimeImmutable
    {
        if ($day = $this->getLastDay()) {
            return $day->getDate();
        }

        return null;
    }

    /**
     * Gets the last day or null if empty.
     */
    public function getLastDay(): ?Day
    {
        return empty($this->days) ? null : \end($this->days);
    }
}
