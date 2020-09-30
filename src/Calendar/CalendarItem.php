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
abstract class CalendarItem implements \JsonSerializable, \ArrayAccess
{
    use DateFormatterTrait;

    /**
     * The calendar.
     *
     * @var Calendar
     */
    protected $calendar;

    /**
     * The custom parameters.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Constructor.
     *
     * @param Calendar $calendar the parent calendar
     */
    protected function __construct(Calendar $calendar)
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
     * Gets the key for the given calendar.
     */
    public static function getCalendarKey(Calendar $calendar): string
    {
        return (string) $calendar->getYear();
    }

    /**
     * Gets the key for the given date.
     */
    public static function getDateKey(\DateTimeInterface $date): string
    {
        return $date->format('d.m.Y');
    }

    /**
     * Gets the key for the given day.
     */
    public static function getDayKey(Day $day): string
    {
        return self::getDateKey($day->getDate());
    }

    /**
     * Gets the unique key.
     */
    abstract public function getKey(): string;

    /**
     * Gets the key for the given month.
     */
    public static function getMonthKey(Month $month): string
    {
        return $month->getFirstDate()->format('n.Y');
    }

    /**
     * Gets the item number.
     */
    abstract public function getNumber(): int;

    /**
     * Gets a custom parameter.
     *
     * @param mixed $key     the parameter key to search for
     * @param mixed $default the default value to return if the parameter does not exist
     *
     * @return mixed|null the parameter value, if found; null otherwise
     */
    public function getParameter($key, $default = null)
    {
        return \array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }

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
     * Gets the key for the given week.
     */
    public static function getWeekKey(Week $week): string
    {
        return $week->getFirstDate()->format('W.Y');
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
     * {@inheritdoc}
     *
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->parameters[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->parameters[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->parameters[] = $value;
        } else {
            $this->parameters[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->parameters[$offset]);
    }

    /**
     * Sets a custom parameter.
     *
     * @param mixed $key   the parameter key
     * @param mixed $value the parameter value
     */
    public function setParameter($key, $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Resets values.
     */
    protected function reset(): void
    {
        $this->parameters = [];
    }
}
