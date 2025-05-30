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

use App\Utils\FormatUtils;
use App\Utils\StringUtils;

/**
 * Represents a week with a calendar and an array of days.
 *
 * @psalm-consistent-constructor
 */
class Week extends AbstractCalendarItem
{
    use DaysTrait;

    /**
     * The date format used to generate this key.
     */
    final public const KEY_FORMAT = 'Y.W';

    /**
     * @param Calendar $calendar the parent calendar
     * @param int      $number   the week number (1-53)
     *
     * @throws CalendarException if the number is not between 1 and 53 inclusive
     */
    public function __construct(Calendar $calendar, protected int $number)
    {
        if ($number < 1 || $number > 53) {
            throw CalendarException::format('The week number %d is not between 1 and 53 inclusive.', $number);
        }
        $key = self::formatKey($calendar->getYear(), $number);
        parent::__construct($calendar, $key);
    }

    #[\Override]
    public function __toString(): string
    {
        $name = StringUtils::getShortName($this);
        $first = (string) FormatUtils::formatDate($this->getFirstDate());
        $last = (string) FormatUtils::formatDate($this->getLastDate());

        return \sprintf('%s(%d-%d, %s - %s)', $name, $this->getNumber(), $this->getYear(), $first, $last);
    }

    /**
     * Gets the key for the given year and week.
     *
     * @param int $year the year
     * @param int $week the week (1-53)
     */
    public static function formatKey(int $year, int $week): string
    {
        return \sprintf('%04d.%02d', $year, $week);
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
        $months = $this->calendar->getMonths();

        return \array_filter($months, function (Month $month) use ($firstDate, $lastDate): bool {
            $monthFirst = $month->getFirstDate();
            if ($firstDate < $monthFirst && $lastDate < $monthFirst) {
                return false;
            }

            $monthLast = $month->getLastDate();

            return !($firstDate > $monthLast && $lastDate > $monthLast);
        });
    }

    /**
     * {@inheritdoc}
     *
     * This implementation returns the ISO-8601 week number (1 to 53) of the year for the last day of this week.
     * The weeks start on Monday.
     */
    #[\Override]
    public function getNumber(): int
    {
        return $this->number;
    }

    #[\Override]
    public function isCurrent(): bool
    {
        $today = $this->getToday();

        return $this->getYear() === $today->getYear() && $this->getNumber() === $today->getWeek();
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
        $months = $this->getMonths();
        $number = $month->getNumber();
        $year = $month->getYear();
        foreach ($months as $current) {
            if ($year === $current->getYear() && $number === $current->getNumber()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{week: int, startDate: string|null, endDate: string|null, days: Day[]}
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'week' => $this->getNumber(),
            'startDate' => FormatUtils::formatDate($this->getFirstDate()),
            'endDate' => FormatUtils::formatDate($this->getLastDate()),
            'days' => $this->days,
        ];
    }

    #[\Override]
    protected function reset(): void
    {
        parent::reset();
        $this->days = [];
    }
}
