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

use App\Util\FormatUtils;
use App\Util\Utils;

/**
 * Represents a single day with a date.
 *
 * @psalm-consistent-constructor
 */
class Day extends AbstractCalendarItem implements WeekDaysInterface, \Stringable
{
    /**
     * The date format used to generate this key.
     */
    final public const KEY_FORMAT = 'Y.m.d';

    /**
     * The date.
     */
    protected \DateTimeImmutable $date;

    /**
     * Constructor.
     *
     * @param Calendar  $calendar the parent calendar
     * @param \DateTime $date     the date
     */
    public function __construct(Calendar $calendar, \DateTimeInterface $date)
    {
        $this->date = \DateTimeImmutable::createFromMutable($date);
        $key = $date->format(self::KEY_FORMAT);
        parent::__construct($calendar, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $name = Utils::getShortName($this);
        $date = (string) FormatUtils::formatDate($this->date);

        return \sprintf('%s(%s)', $name, $date);
    }

    /**
     * Returns this date formatted according to given format.
     *
     * @param string $format a format accepted by date
     *
     * @return string|false the formatted date string on success or false on failure
     */
    public function format(string $format): string|false
    {
        return $this->date->format($format);
    }

    /**
     * Gets the date.
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Gets the day of the year (0 - 365).
     */
    public function getDayOfYear(): int
    {
        return (int) $this->format('z');
    }

    /**
     * Gets the month number (1 to 12).
     */
    public function getMonth(): int
    {
        return (int) $this->format('n');
    }

    /**
     * Gets this full name.
     */
    public function getName(): string
    {
        $names = $this->calendar->getWeekNames();

        return $names[$this->getWeekDay()];
    }

    /**
     * This implementation returns the day of the month (1 to 31).
     */
    public function getNumber(): int
    {
        return (int) $this->format('j');
    }

    /**
     * Gets this short name.
     */
    public function getShortName(): string
    {
        $names = $this->calendar->getWeekShortNames();

        return $names[$this->getWeekDay()];
    }

    /**
     * Gets the Unix timestamp. This is a shortcut for:
     * <pre>
     * <code>
     * $day->getDate()->getTimestamp();
     * </code>
     * </pre>.
     *
     * @return int the Unix timestamp representing this date
     */
    public function getTimestamp(): int
    {
        return $this->date->getTimestamp();
    }

    /**
     * Gets ISO-8601 week number of year, weeks starting on Monday.
     *
     * @return int 1 to 53
     */
    public function getWeek(): int
    {
        return (int) $this->format('W');
    }

    /**
     * Gets ISO-8601 numeric representation of the day of the week.
     *
     * @return int 1 (for Monday) through 7 (for Sunday)
     */
    public function getWeekDay(): int
    {
        return (int) $this->format('N');
    }

    /**
     * {@inheritdoc}
     */
    public function getYear(): int
    {
        return (int) $this->format('Y');
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
        $today = $this->getToday();

        return $this->getYear() === $today->getYear()
            && $this->getMonth() === $today->getMonth()
            && $this->getNumber() === $today->getNumber();
    }

    /**
     * Returns if this is the first day of the week (monday).
     */
    public function isFirstInWeek(): bool
    {
        return self::MONDAY === $this->getWeekDay();
    }

    /**
     * Returns if this day in within the given month.
     *
     * @param Month $month the month to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInMonth(Month $month): bool
    {
        return ($this->getMonth() === $month->getNumber()) && ($this->getYear() === $month->getYear());
    }

    /**
     * Returns if this day in within the given week.
     *
     * @param Week $week the week to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInWeek(Week $week): bool
    {
        return ($this->getWeek() === $week->getNumber()) && ($this->getYear() === $week->getYear());
    }

    /**
     * Returns if this day in within the given year.
     *
     * @param int $year the year to be tested
     *
     * @return bool true if within; false otherwise
     */
    public function isInYear(int $year): bool
    {
        return $this->getYear() === $year;
    }

    /**
     * Returns if this is the last day of the week (sunday).
     */
    public function isLastInWeek(): bool
    {
        return self::SUNDAY === $this->getWeekDay();
    }

    /**
     * Returns if this day is in the weekend (saturday or sunday).
     */
    public function isWeekend(): bool
    {
        return \in_array($this->getWeekDay(), [self::SATURDAY, self::SUNDAY], true);
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'day' => $this->getNumber(),
            'name' => $this->getName(),
            'shortName' => $this->getShortName(),
            'date' => FormatUtils::formatDate($this->date),
        ];
    }
}
