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

use App\Traits\DateFormatterTrait;
use App\Util\Utils;

/**
 * Base class for calendar objects.
 *
 * @author Laurent Muller
 */
abstract class CalendarItem implements \JsonSerializable
{
    use DateFormatterTrait;

    /**
     * The parent's calendar.
     *
     * @var Calendar
     */
    protected $calendar;

    /**
     * The item's key.
     *
     * @var string
     */
    protected $key;

    /**
     * Constructor.
     *
     * @param Calendar $calendar the parent's calendar
     * @param string   $key      the unique key
     */
    public function __construct(Calendar $calendar, string $key)
    {
        $this->calendar = $calendar;
        $this->key = $key;
        $this->reset();
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%d)', $name, $this->getNumber());
    }

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
     *
     * @return \App\Calendar\Day
     */
    public function getToday(): Day
    {
        return $this->calendar->getToday();
    }

    /**
     * Gets the year as 4 digits (Examples: 1999 or 2003).
     */
    public function getYear(): int
    {
        return $this->calendar->getYear();
    }

    /**
     * Returns if this item is the current item (for example curent month, current week or the current day).
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
