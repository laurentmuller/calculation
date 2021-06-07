<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Calendar;

use App\Util\FormatUtils;
use App\Util\Utils;

/**
 * Represents a month with a calendar and an array of days.
 *
 * @author Laurent Muller
 */
class Month extends AbstractCalendarItem
{
    use DaysTrait;

    /**
     * The date format used to generate this key.
     */
    public const KEY_FORMAT = 'Y.m';

    /**
     * The month number (1 - 12).
     */
    protected int $number;

    /**
     * Constructor.
     *
     * @param Calendar $calendar the parent calendar
     * @param int      $number   the month number (1 - 12)
     *
     * @throws CalendarException if the number is not between 1 and 12 inclusive
     */
    public function __construct(Calendar $calendar, int $number)
    {
        if ($number < 1 || $number > 12) {
            throw new CalendarException("The month number $number is not between 1 and 12 inclusive.");
        }

        $key = self::formatKey($calendar->getYear(), $number);
        parent::__construct($calendar, $key);
        $this->number = $number;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $name = Utils::getShortName($this);
        $first = FormatUtils::formatDate($this->getFirstDate());
        $last = FormatUtils::formatDate($this->getLastDate());

        return \sprintf('%s(%d.%d, %s - %s)',
            $name, $this->getNumber(), $this->getYear(), $first, $last);
    }

    /**
     * Gets the key for the given year and month.
     *
     * @param int $year  the year
     * @param int $month the month (1 - 12)
     */
    public static function formatKey(int $year, int $month): string
    {
        return \sprintf('%04d.%02d', $year, $month);
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
     * This implementation returns the month number (1 to 12).
     */
    public function getNumber(): int
    {
        return $this->number;
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
     * Gets the calendar's weeks that this month is contained in.
     *
     * @return Week[]
     */
    public function getWeeks(): array
    {
        $firstDate = $this->getFirstDate();
        $lastDate = $this->getLastDate();
        $weeks = $this->calendar->getWeeks();

        $result = \array_filter($weeks, function (Week $week) use ($firstDate, $lastDate): bool {
            $weekFirst = $week->getFirstDate();
            if ($firstDate < $weekFirst && $lastDate < $weekFirst) {
                return false;
            }

            $weekLast = $week->getLastDate();
            if ($firstDate > $weekLast && $lastDate > $weekLast) {
                return false;
            }

            return true;
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
        $today = $this->getToday();

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
        $number = $week->getNumber();
        $year = $week->getYear();

        foreach ($weeks as $current) {
            if ($year === $current->getYear()
                && $number === $current->getNumber()) {
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
            'startDate' => FormatUtils::formatDate($this->getFirstDate()),
            'endDate' => FormatUtils::formatDate($this->getLastDate()),
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
