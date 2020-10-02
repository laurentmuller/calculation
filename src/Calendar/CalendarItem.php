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
     * The calendar.
     *
     * @var Calendar
     */
    protected $calendar;

    /**
     * Constructor.
     *
     * @param Calendar $calendar the parent calendar
     */
    public function __construct(Calendar $calendar)
    {
        $this->calendar = $calendar;
        $this->reset();
    }

    public function __toString(): string
    {
        $name = $name = Utils::getShortName($this);

        return \sprintf('%s(%d)', $name, $this->getNumber());
    }

    /**
     * Gets the parent calendar.
     */
    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    /**
     * Gets the unique key.
     */
    abstract public function getKey(): string;

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
     * Gets the year.
     */
    public function getYear(): int
    {
        return $this->calendar->getYear();
    }

    /**
     * Returns if this item is the current item (Curent month, current week or the current day).
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
