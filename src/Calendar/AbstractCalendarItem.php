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

/**
 * Base class for calendar objects.
 */
abstract class AbstractCalendarItem implements \JsonSerializable, \Stringable
{
    /**
     * @param Calendar $calendar the parent's calendar
     * @param string   $key      the unique key
     */
    public function __construct(protected Calendar $calendar, protected string $key)
    {
        $this->reset();
    }

    abstract public function __toString(): string;

    /**
     * Gets the parent's calendar.
     */
    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    /**
     * Gets the unique key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Gets the item number.
     */
    abstract public function getNumber(): int;

    /**
     * Gets the today day.
     */
    public function getToday(): Day
    {
        return $this->calendar->getToday();
    }

    /**
     * Gets the year as four digits (Examples: 1999 or 2003).
     */
    public function getYear(): int
    {
        return $this->calendar->getYear();
    }

    /**
     * Returns if this item is the current item (for example, current month, current week or the current day).
     *
     * @return bool true if current
     */
    abstract public function isCurrent(): bool;

    /**
     * Resets values.
     */
    protected function reset(): void
    {
    }
}
