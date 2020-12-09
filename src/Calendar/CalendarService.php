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

/**
 * Creates an instance of calendar suitable for rendering in Twig template.
 *
 * @author Laurent Muller
 */
class CalendarService
{
    use ModelTrait;

    /**
     * The default calendar model class.
     */
    public const DEFAULT_CALENDAR_MODEL = Calendar::class;

    /**
     * The calendar model class.
     *
     * @var string
     */
    private $calendarModel = self::DEFAULT_CALENDAR_MODEL;

    /**
     * The day model.
     *
     * @var string
     */
    private $dayModel;

    /**
     * The month model.
     *
     * @var string
     */
    private $monthModel;

    /**
     * The week model.
     *
     * @var string
     */
    private $weekModel;

    /**
     * Returns a calendar for specified year.
     *
     * @param int $year the year to generate calendar for or null for the current year
     *
     * @return Calendar the generated calendar
     *
     * @throws CalendarException if the month, the week or the day class model are set and does not exist
     */
    public function generate(?int $year = null): Calendar
    {
        // check year
        $year = DateUtils::completYear($year ?? \date('Y'));

        /** @var Calendar $calendar */
        $calendar = new $this->calendarModel();
        $calendar->setModels($this->monthModel, $this->weekModel, $this->dayModel);
        $calendar->generate($year);

        return $calendar;
    }

    /**
     * Gets the calendar model class.
     *
     * @return string the calendar model
     */
    public function getCalendarModel(): string
    {
        return $this->calendarModel;
    }

    /**
     * Gets the day model class.
     *
     * @return string|null the day model or null if default
     */
    public function getDayModel(): ?string
    {
        return $this->dayModel;
    }

    /**
     * Gets the month model class.
     *
     * @return string|null the month model or null if default
     */
    public function getMonthModel(): ?string
    {
        return $this->monthModel;
    }

    /**
     * Gets the week model class.
     *
     * @return string|null the week model or null if default
     */
    public function getWeekModel(): ?string
    {
        return $this->weekModel;
    }

    /**
     * Sets the calendar model class.
     *
     * @param string|null $calendarModel the calendar model class or null for default
     *
     * @throws CalendarException if the calendar class model does not exist
     */
    public function setCalendarModel(?string $calendarModel): self
    {
        $this->calendarModel = $this->checkClass($calendarModel, self::DEFAULT_CALENDAR_MODEL);

        return $this;
    }

    /**
     * Sets the day model class.
     *
     * @param string|null $dayModel the day model class or null for default
     *
     * @throws CalendarException if the day class model does not exist
     */
    public function setDayModel(?string $dayModel): self
    {
        $this->checkClass($dayModel, Calendar::DEFAULT_DAY_MODEL);
        $this->dayModel = $dayModel;

        return $this;
    }

    /**
     * Sets the models.
     *
     * @param string|null $calendarModel the calendar model class or null for default
     * @param string|null $monthModel    the month model class or null for default
     * @param string|null $weekModel     the week model class or null for default
     * @param string|null $dayModel      the day model class or null for default
     *
     * @throws CalendarException if the calendar, the month, the week or the day class model does not exist
     */
    public function setModels(?string $calendarModel = null, ?string $monthModel = null, ?string $weekModel = null, ?string $dayModel = null): self
    {
        return $this->setCalendarModel($calendarModel)
            ->setMonthModel($monthModel)
            ->setWeekModel($weekModel)
            ->setDayModel($dayModel);
    }

    /**
     * Sets the month model class.
     *
     * @param string|null $monthModel the month model class or null for default
     *
     * @throws CalendarException if the month class model does not exist
     */
    public function setMonthModel(?string $monthModel): self
    {
        $this->checkClass($monthModel, Calendar::DEFAULT_MONTH_MODEL);
        $this->monthModel = $monthModel;

        return $this;
    }

    /**
     * Sets the week model class.
     *
     * @param string|null $weekModel the week model class or null for default
     *
     * @throws CalendarException if the week class model does not exist
     */
    public function setWeekModel(?string $weekModel): self
    {
        $this->checkClass($weekModel, Calendar::DEFAULT_WEEK_MODEL);
        $this->weekModel = $weekModel;

        return $this;
    }
}
