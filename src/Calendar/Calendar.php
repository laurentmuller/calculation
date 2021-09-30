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

use App\Util\DateUtils;
use App\Util\FormatUtils;
use App\Util\Utils;

/**
 * Represents a calendar for a specified year.
 *
 * @author Laurent Muller
 */
class Calendar extends AbstractCalendarItem implements MonthsInterface, WeekDaysInterface
{
    use DaysTrait;
    use ModelTrait;

    /**
     * The default day model class.
     */
    public const DEFAULT_DAY_MODEL = Day::class;

    /**
     * The default month model class.
     */
    public const DEFAULT_MONTH_MODEL = Month::class;

    /**
     * The default week model class.
     */
    public const DEFAULT_WEEK_MODEL = Week::class;

    /**
     * The day model class.
     */
    protected string $dayModel = self::DEFAULT_DAY_MODEL;

    /**
     * The month model class.
     */
    protected string $monthModel = self::DEFAULT_MONTH_MODEL;

    /**
     * The full month names.
     *
     * @var string[]
     */
    protected ?array $monthNames = null;

    /**
     * Array with instances of Month objects.
     *
     * @var Month[]
     */
    protected array $months;

    /**
     * The short month names.
     *
     * @var string[]
     */
    protected ?array $monthShortNames = null;

    /**
     * The today day.
     */
    protected ?Day $today = null;

    /**
     * The week model class.
     */
    protected string $weekModel = self::DEFAULT_WEEK_MODEL;

    /**
     * The full name of the week days.
     *
     * @var string[]
     */
    protected ?array $weekNames = null;

    /**
     * Array with instances of Week objects.
     *
     * @var Week[]
     */
    protected array $weeks;

    /**
     * The short name of the week days.
     *
     * @var string[]
     */
    protected ?array $weekShortNames = null;

    /**
     * Year for calendar.
     */
    protected ?int $year = null;

    /**
     * Constructor.
     *
     * @param int $year the year to generate
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
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $name = Utils::getShortName($this);
        $firstDate = new \DateTime('1 January ' . $this->year);
        $lastDate = new \DateTime('31 December ' . $this->year);
        $first = FormatUtils::formatDate($firstDate);
        $last = FormatUtils::formatDate($lastDate);

        return \sprintf('%s(%d, %s - %s)', $name, $this->getNumber(), $first, $last);
    }

    /**
     * Generates months, weeks and days for the given year.
     *
     * @param int $year the year to generate
     */
    public function generate(int $year): self
    {
        // check year
        $this->year = DateUtils::completYear($year);
        $this->key = (string) $this->year;

        // clean
        $this->reset();

        // get first and last days of the year
        $firstYearDate = new \DateTime('1 January ' . $year);
        $lastYearDate = new \DateTime('31 December ' . $year);

        // get first day in calendar (monday of the 1st week)
        /** @var \DateTime $firstDate */
        $firstDate = new \DateTime('first monday of January ' . $year);
        if ($firstDate > $firstYearDate) {
            $firstDate->sub(new \DateInterval('P1W'));
        }

        // get the last days in calendar (sunday of the last week)
        /** @var \DateTime $lastDate */
        $lastDate = new \DateTime('last sunday of December ' . $year);
        if ($lastDate < $lastYearDate) {
            $lastDate->add(new \DateInterval('P1W'));
        }

        /** @var ?Week $currentWeek */
        $currentWeek = null;

        /** @var ?Month $currentMonth */
        $currentMonth = null;

        /** @var \DateTime $currentDate */
        $currentDate = clone $firstDate;

        // build calendar
        $interval = new \DateInterval('P1D');
        while ($currentDate <= $lastDate) {
            // add day
            $day = $this->createDay($currentDate);

            // calculate numbers
            $monthYear = (int) $currentDate->format('Y');
            $monthNumber = (int) $currentDate->format('n');
            $weekNumber = (int) $currentDate->format('W');

            if ($monthYear === $this->year) {
                // create month if needed
                if (null === $currentMonth || $currentMonth->getNumber() !== $monthNumber) {
                    $currentMonth = $this->createMonth($monthNumber);
                }
                $currentMonth->addDay($day);
            }

            // create week if needed
            if (null === $currentWeek || $currentWeek->getNumber() !== $weekNumber) {
                $currentWeek = $this->createWeek($weekNumber);
            }
            $currentWeek->addDay($day);

            // next day
            $currentDate->add($interval);
        }

        return $this;
    }

    /**
     * Gets the month for the given key.
     *
     * @param int|\DateTimeInterface|string $key the month key. Can be an integer (1 - 12), a date time interface or a formatted date ('Y.m').
     *
     * @return Month|null the month, if found, null otherwise
     *
     * @see Month::KEY_FORMAT
     */
    public function getMonth($key): ?Month
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
     * @return string[]
     */
    public function getMonthNames(): array
    {
        if (!$this->monthNames) {
            $this->monthNames = DateUtils::getMonths();
        }

        return $this->monthNames;
    }

    /**
     * Gets months where key is month number (1 - 12).
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
     * @return string[]
     */
    public function getMonthShortNames(): array
    {
        if (!$this->monthShortNames) {
            $this->monthShortNames = DateUtils::getShortMonths();
        }

        return $this->monthShortNames;
    }

    /**
     * This implementation returns the generated year.
     */
    public function getNumber(): int
    {
        return $this->getYear();
    }

    /**
     * {@inheritdoc}
     */
    public function getToday(): Day
    {
        if (null === $this->today) {
            $date = new \DateTime('today');
            $this->today = new Day($this, $date);
        }

        return $this->today;
    }

    /**
     * Gets the week for the given key.
     *
     * @param int|\DateTimeInterface|string $key the week key. Can be an integer (1 - 53), a date time interface or a formatted date ('Y.W').
     *
     * @return Week|null the week, if found, null otherwise
     *
     * @see Week::KEY_FORMAT
     */
    public function getWeek($key): ?Week
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
     * @return string[]
     */
    public function getWeekNames(): array
    {
        if (!$this->weekNames) {
            $this->weekNames = DateUtils::getWeekdays('monday');
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
     * @return string[]
     */
    public function getWeekShortNames(): array
    {
        if (!$this->weekShortNames) {
            $this->weekShortNames = DateUtils::getShortWeekdays('monday');
        }

        return $this->weekShortNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getYear(): int
    {
        return (int) $this->year;
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
        $today = $this->getToday();

        return $this->getYear() === $today->getYear();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'year' => $this->year,
            'startDate' => (string) FormatUtils::formatDate($this->getFirstDate()),
            'endDate' => (string) FormatUtils::formatDate($this->getLastDate()),
        ];
    }

    /**
     * Sets the models.
     *
     * @param string|null $monthModel the month model class or null for default
     * @param string|null $weekModel  the week model class or null for default
     * @param string|null $dayModel   the day model class or null for default
     *
     * @throws CalendarException if the month, the week or the day class model does not exist
     */
    public function setModels(?string $monthModel = null, ?string $weekModel = null, ?string $dayModel = null): self
    {
        $this->monthModel = $this->checkClass($monthModel, self::DEFAULT_MONTH_MODEL);
        $this->weekModel = $this->checkClass($weekModel, self::DEFAULT_WEEK_MODEL);
        $this->dayModel = $this->checkClass($dayModel, self::DEFAULT_DAY_MODEL);

        return $this;
    }

    /**
     * Sets the full name of the months. The array must have 12 values and keys from 1 to 12.
     *
     * @param string[] $monthNames the month names to set
     *
     * @throws CalendarException if the array does not contains 12 values, if a key is missing or if one of the values is not a string
     */
    public function setMonthNames(array $monthNames): self
    {
        $this->monthNames = $this->checkArray($monthNames, self::MONTHS_COUNT);

        return $this;
    }

    /**
     * Sets the short name of the months. The array must have 12 values and keys from 1 to 12.
     *
     * @param string[] $monthShortNames the month short names to set
     *
     * @throws CalendarException if the array does not contains 12 values, if a key is missing or if one of the values is not a string
     */
    public function setMonthShortNames(array $monthShortNames): self
    {
        $this->monthShortNames = $this->checkArray($monthShortNames, self::MONTHS_COUNT);

        return $this;
    }

    /**
     * Sets the full name of the week days. The array must have 7 values and keys from 1 to 7.
     *
     * @param string[] $weekNames the week names to set
     *
     * @throws CalendarException if the array does not contains 7 values, if a key is missing or if one of the values is not a string
     */
    public function setWeekNames(array $weekNames): self
    {
        $this->weekNames = $this->checkArray($weekNames, self::DAYS_COUNT);

        return $this;
    }

    /**
     * Sets the short name of the week days. The array must have 7 values and keys from 1 to 7.
     *
     * @param string[] $weekShortNames the week short names to set
     *
     * @throws CalendarException if the array does not contains 7 values, if a key is missing or if one of the values is not a string
     */
    public function setWeekShortNames(array $weekShortNames): self
    {
        $this->weekShortNames = $this->checkArray($weekShortNames, self::DAYS_COUNT);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function reset(): void
    {
        parent::reset();
        $this->months = [];
        $this->weeks = [];
        $this->days = [];
    }

    /**
     * Checks if the given array has the given length and that all keys from 1 to length are present.
     *
     * @param array $array  the array to verify
     * @param int   $length the length to match
     *
     * @return array the given array
     *
     * @throws CalendarException if the array has the wrong length, if a key is missing or if one of the values is not a string
     */
    private function checkArray(array $array, int $length): array
    {
        if ($length !== \count($array)) {
            throw new CalendarException("The array must contains {$length} values.");
        }
        for ($i = 1; $i <= $length; ++$i) {
            if (!\array_key_exists($i, $array)) {
                throw new CalendarException("The array must contains the key {$i}.");
            }
            if (!\is_string($array[$i])) {
                throw new CalendarException("The value {$array[$i]} for the key {$i} must be a string.");
            }
        }

        return $array;
    }

    /**
     * Create and add a day.
     *
     * @param \DateTime $date the day date
     */
    private function createDay(\DateTimeInterface $date): Day
    {
        /** @var Day $day */
        $day = new $this->dayModel($this, $date);
        $this->addDay($day);

        return $day;
    }

    /**
     * Create and add a month.
     *
     * @param int $number the month number (1 - 12)
     *
     * @throws CalendarException if the number is not between 1 and 12 inclusive
     */
    private function createMonth(int $number): Month
    {
        /** @var Month $month */
        $month = new $this->monthModel($this, $number);
        $this->months[$month->getKey()] = $month;

        return $month;
    }

    /**
     * Create and add a week.
     *
     * @param int $number the week number (1 - 53)
     *
     * @throws CalendarException if the number is not between 1 and 53 inclusive
     */
    private function createWeek(int $number): Week
    {
        /** @var Week $week */
        $week = new $this->weekModel($this, $number);
        $this->weeks[] = $week;

        return $week;
    }
}
