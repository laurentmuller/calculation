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

/**
 * Creates an instance of calendar suitable for rendering in Twig template.
 */
class CalendarService
{
    use ModelTrait;

    /**
     * The default calendar model class.
     */
    final public const DEFAULT_CALENDAR_MODEL = Calendar::class;

    /**
     * The calendar model class.
     *
     * @var class-string<Calendar>
     */
    private string $calendarModel = self::DEFAULT_CALENDAR_MODEL;

    /**
     * Returns a calendar for the specified year.
     *
     * @param ?int $year the year to generate the calendar for or null for the current year
     *
     * @return Calendar the generated calendar
     *
     * @throws CalendarException
     */
    public function generate(?int $year = null): Calendar
    {
        $year = DateUtils::completYear($year ?? (int) \date('Y'));

        return $this->createCalendar()
            ->setMonthModel($this->getMonthModel())
            ->setWeekModel($this->getWeekModel())
            ->setDayModel($this->getDayModel())
            ->generate($year);
    }

    /**
     * Gets the calendar model class.
     *
     * @return class-string<Calendar> the calendar model
     *
     * @psalm-api
     */
    public function getCalendarModel(): string
    {
        return $this->calendarModel;
    }

    /**
     * Sets the calendar model class.
     *
     * @param class-string<Calendar>|null $calendarModel the calendar model class or null for default
     *
     * @throws CalendarException if the calendar class model does not exist
     *
     * @psalm-api
     */
    public function setCalendarModel(?string $calendarModel): self
    {
        $this->calendarModel = $this->checkClass($calendarModel, self::DEFAULT_CALENDAR_MODEL);

        return $this;
    }

    private function createCalendar(): Calendar
    {
        return new $this->calendarModel();
    }
}
