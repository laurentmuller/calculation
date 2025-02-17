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

use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;

/**
 * Represents a calendar for a specified year.
 *
 * @psalm-consistent-constructor
 */
class Calendar extends AbstractCalendarItem implements \Stringable, MonthsInterface, WeekDaysInterface
{
    use DaysTrait;
    use ModelTrait;

    /**
     * The default day model class.
     */
    final public const DEFAULT_DAY_MODEL = Day::class;

    /**
     * The default month model class.
     */
    final public const DEFAULT_MONTH_MODEL = Month::class;

    /**
     * The default week model class.
     */
    final public const DEFAULT_WEEK_MODEL = Week::class;

    /**
     * The full month names.
     *
     * @var array<int, string>
     */
    private ?array $monthNames = null;

    /**
     * Array with instances of Month objects.
     *
     * @var Month[]
     */
    private array $months = [];

    /**
     * The short month names.
     *
     * @var array<int, string>
     */
    private ?array $monthShortNames = null;

    /**
     * The today day.
     */
    private ?Day $today = null;

    /**
     * The full name of the week days.
     *
     * @var array<int, string>
     */
    private ?array $weekNames = null;

    /**
     * Array with instances of Week objects.
     *
     * @var Week[]
     */
    private array $weeks = [];

    /**
     * The short name of the week days.
     *
     * @var array<int, string>
     */
    private ?array $weekShortNames = null;

    /**
     * Year for the calendar.
     */
    private ?int $year = null;

    /**
     * @param ?int $year the year to generate
     *
     * @throws CalendarException
     */
    public function __construct(?int $year = null)
    {
        parent::__construct($this, (string) ($year ?? 0));

        // generate if applicable
        if (null !== $year) {
            $this->generate($year);
        }
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function __toString(): string
    {
        $year = (int) $this->year;
        $name = StringUtils::getShortName($this);
        $firstDate = new \DateTimeImmutable(\sprintf('%d-01-01', $year));
        $lastDate = new \DateTimeImmutable(\sprintf('%d-12-31', $year));
        $first = FormatUtils::formatDate($firstDate);
        $last = FormatUtils::formatDate($lastDate);

        return \sprintf('%s(%d, %s - %s)', $name, $this->getNumber(), $first, $last);
    }

    /**
     * Generates months, weeks and days for the given year.
     *
     * @param int $year the year to generate
     *
     * @throws \Exception
     * @throws CalendarException
     */
    public function generate(int $year): self
    {
        $this->year = DateUtils::completYear($year);
        $this->key = (string) $this->year;
        $this->reset();
        $firstYearDate = new \DateTimeImmutable(\sprintf('1 January %d', $this->year));
        $lastYearDate = new \DateTimeImmutable(\sprintf('31 December %d', $this->year));
        $firstDate = new \DateTime(\sprintf('first monday of January %s', $this->year));
        if ($firstDate > $firstYearDate) {
            $firstDate->sub(new \DateInterval('P1W'));
        }
        $lastDate = new \DateTime(\sprintf('last sunday of December %d', $this->year));
        if ($lastDate < $lastYearDate) {
            $lastDate->add(new \DateInterval('P1W'));
        }
        /** @var ?Week $currentWeek */
        $currentWeek = null;
        /** @var ?Month $currentMonth */
        $currentMonth = null;
        $interval = new \DateInterval('P1D');
        while ($firstDate <= $lastDate) {
            $day = $this->createDay($firstDate);
            $monthYear = DateUtils::getYear($firstDate);
            $monthNumber = DateUtils::getMonth($firstDate);
            $weekNumber = DateUtils::getWeek($firstDate);
            if ($monthYear === $this->year) {
                if (!$currentMonth instanceof Month || $currentMonth->getNumber() !== $monthNumber) {
                    $currentMonth = $this->createMonth($monthNumber);
                }
                $currentMonth->addDay($day);
            }
            if (!$currentWeek instanceof Week || $currentWeek->getNumber() !== $weekNumber) {
                $currentWeek = $this->createWeek($weekNumber);
            }
            $currentWeek->addDay($day);
            $firstDate->add($interval);
        }

        return $this;
    }

    /**
     * Gets the month for the given key.
     *
     * @param \DateTimeInterface|int|string $key the month key. Can be an integer (1-12), a date time interface or a formatted date ('Y.m').
     *
     * @return Month|null the month, if found, null otherwise
     *
     * @see Month::KEY_FORMAT
     */
    public function getMonth(\DateTimeInterface|int|string $key): ?Month
    {
        if ($key instanceof \DateTimeInterface) {
            $key = $key->format(Month::KEY_FORMAT);
        }
        if (\is_int($key)) {
            foreach ($this->months as $month) {
                if ($key === $month->getNumber()) {
                    return $month;
                }
            }
        }

        return $this->months[(string) $key] ?? null;
    }

    /**
     * Gets the full name of the months.
     *
     * @return array<int, string>
     */
    public function getMonthNames(): array
    {
        if (null === $this->monthNames) {
            $this->monthNames = DateUtils::getMonths();
        }

        return $this->monthNames;
    }

    /**
     * Gets months when the key is month number (1-12).
     *
     * @return Month[]
     */
    public function getMonths(): array
    {
        return $this->months;
    }

    /**
     * Gets the short name of the months.
     *
     * @return array<int, string>
     */
    public function getMonthShortNames(): array
    {
        if (null === $this->monthShortNames) {
            $this->monthShortNames = DateUtils::getShortMonths();
        }

        return $this->monthShortNames;
    }

    /**
     * This implementation returns the generated year.
     */
    #[\Override]
    public function getNumber(): int
    {
        return $this->getYear();
    }

    #[\Override]
    public function getToday(): Day
    {
        if (!$this->today instanceof Day) {
            $date = new \DateTime('today');
            $this->today = new Day($this, $date);
        }

        return $this->today;
    }

    /**
     * Gets the week for the given key.
     *
     * @param \DateTimeInterface|int|string $key the week key. Can be an integer (1-53), a date time interface
     *                                           or a formatted date ('Y.W').
     *
     * @return Week|null the week, if found, null otherwise
     *
     * @see Week::KEY_FORMAT
     */
    public function getWeek(\DateTimeInterface|int|string $key): ?Week
    {
        if ($key instanceof \DateTimeInterface) {
            $key = $key->format(Week::KEY_FORMAT);
        }
        foreach ($this->weeks as $week) {
            if ($key === $week->getKey()) {
                return $week;
            }
        }

        return $this->weeks[(string) $key] ?? null;
    }

    /**
     * Gets the full name of the week days.
     *
     * @return array<int, string>
     */
    public function getWeekNames(): array
    {
        if (null === $this->weekNames) {
            $this->weekNames = DateUtils::getWeekdays();
        }

        return $this->weekNames;
    }

    /**
     * Gets weeks.
     *
     * @return Week[]
     */
    public function getWeeks(): array
    {
        return $this->weeks;
    }

    /**
     * Gets the short name of the week days.
     *
     * @return array<int, string>
     */
    public function getWeekShortNames(): array
    {
        if (null === $this->weekShortNames) {
            $this->weekShortNames = DateUtils::getShortWeekdays();
        }

        return $this->weekShortNames;
    }

    #[\Override]
    public function getYear(): int
    {
        return (int) $this->year;
    }

    #[\Override]
    public function isCurrent(): bool
    {
        $today = $this->getToday();

        return $this->getYear() === $today->getYear();
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'year' => $this->year,
            'startDate' => (string) FormatUtils::formatDate($this->getFirstDate()),
            'endDate' => (string) FormatUtils::formatDate($this->getLastDate()),
        ];
    }

    #[\Override]
    protected function reset(): void
    {
        parent::reset();
        $this->months = [];
        $this->weeks = [];
        $this->days = [];
    }

    /**
     * Create and add a day.
     *
     * @param \DateTime $date the day date
     */
    private function createDay(\DateTimeInterface $date): Day
    {
        $day = new $this->dayModel($this, $date);
        $this->addDay($day);

        return $day;
    }

    /**
     * Create and add a month.
     *
     * @param int $number the month number (1-12)
     *
     * @throws CalendarException if the number is not between 1 and 12 inclusive
     */
    private function createMonth(int $number): Month
    {
        $month = new $this->monthModel($this, $number);
        $this->months[$month->getKey()] = $month;

        return $month;
    }

    /**
     * Create and add a week.
     *
     * @param int $number the week number (1-53)
     */
    private function createWeek(int $number): Week
    {
        $week = new $this->weekModel($this, $number);
        $this->weeks[] = $week;

        return $week;
    }
}
