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

namespace App\Calendar;

use Symfony\Component\Clock\DatePoint;

/**
 * Trait to manage an array of days.
 */
trait DaysTrait
{
    /**
     * The days.
     *
     * @var Day[]
     */
    private array $days = [];

    /**
     * Adds a day.
     */
    public function addDay(Day $day): self
    {
        $this->days[$day->getKey()] = $day;

        return $this;
    }

    /**
     * Gets the day for the given key.
     *
     * @param DatePoint|string $key the day key. Can be an integer, a date time interface or a formatted date ('Y.m.d').
     *
     * @return Day|null the day, if found, null otherwise
     *
     * @see Day::KEY_FORMAT
     */
    public function getDay(DatePoint|string $key): ?Day
    {
        if ($key instanceof DatePoint) {
            $key = $key->format(Day::KEY_FORMAT);
        }

        return $this->days[$key] ?? null;
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
    public function getFirstDate(): ?DatePoint
    {
        return $this->getFirstDay()?->getDate();
    }

    /**
     * Gets first day or null if empty.
     */
    public function getFirstDay(): ?Day
    {
        return [] === $this->days ? null : \reset($this->days);
    }

    /**
     * Gets the last date or null if empty.
     */
    public function getLastDate(): ?DatePoint
    {
        return $this->getLastDay()?->getDate();
    }

    /**
     * Gets the last day or null if empty.
     */
    public function getLastDay(): ?Day
    {
        return [] === $this->days ? null : \end($this->days);
    }
}
