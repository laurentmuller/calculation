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
    const DEFAULT_CALENDAR_MODEL = Calendar::class;

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
     * @throws \Exception if the month, the week or the day class model are set and does not exist
     */
    public function generate(?int $year = null): Calendar
    {
        $year = $year ?: \date('Y');
        if ($year < 100) {
            $dt = \DateTime::createFromFormat('y', $year);
            $year = $dt->format('Y');
        }

        /** @var Calendar $calendar */
        $calendar = new $this->calendarModel();
        $calendar->setModels($this->monthModel, $this->weekModel, $this->dayModel);
        $calendar->generate($year);

        return $calendar;
    }

    /**
     * Sets the models.
     *
     * @param string $calendarModel the calendar model class or null for default
     * @param string $monthModel    the month model class or null for default
     * @param string $weekModel     the week model class or null for default
     * @param string $dayModel      the day model class or null for default
     *
     * @throws \Exception if the calendar, the month, the week or the day class model does not exist
     */
    public function setModels(?string $calendarModel = null, ?string $monthModel = null, ?string $weekModel = null, ?string $dayModel = null): self
    {
        $this->calendarModel = $this->checkClass($calendarModel, self::DEFAULT_CALENDAR_MODEL);

        $this->monthModel = $monthModel;
        $this->weekModel = $weekModel;
        $this->dayModel = $dayModel;

        return $this;
    }
}
