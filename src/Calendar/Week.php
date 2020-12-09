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
 * Represents a week with a calendar and an array of days.
 *
 * @author Laurent Muller
 */
class Week extends CalendarItem
{
    use DaysTrait;

    /**
     * The date format used to generate this key.
     */
    public const KEY_FORMAT = 'Y.W';

    /**
     * The week number (1 - 53).
     *
     * @var int
     */
    protected $number;

    /**
     * Constructor.
     *
     * @param Calendar $calendar the parent calendar
     * @param int      $number   the week number (1 - 53)
     *
     * @throws CalendarException if the number is not between 1 and 53 inclusive
     */
    public function __construct(Calendar $calendar, int $number)
    {
        if ($number < 1 || $number > 53) {
            throw new CalendarException("The week number $number is not between 1 and 53 inclusive.");
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

        return \sprintf('%s(%d-%d, %s - %s)',
            $name, $this->getNumber(), $this->getYear(), $first, $last);
    }

    /**
     * Gets the key for the given year and week.
     *
     * @param int $year the year
     * @param int $week the week (1 - 53)
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

        $result = \array_filter($months, function (Month $month) use ($firstDate, $lastDate) {
            $monthFirst = $month->getFirstDate();
            if ($firstDate < $monthFirst && $lastDate < $monthFirst) {
                return false;
            }

            $monthLast = $month->getLastDate();
            if ($firstDate > $monthLast && $lastDate > $monthLast) {
                return false;
            }

            return true;
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * This implementation returns the ISO-8601 week number (1 to 53) of year for the last day of this week.
     * The weeks start on Monday.
     */
    public function getNumber(): int
    {
        return $this->number;
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
        $number = $month->getNumber();
        $year = $month->getYear();

        foreach ($months as $current) {
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
            'week' => $this->getNumber(),
            'startDate' => FormatUtils::formatDate($this->getFirstDate()),
            'endDate' => FormatUtils::formatDate($this->getLastDate()),
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
