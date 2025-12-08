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

/**
 * Trait to check for model class name.
 */
trait ModelTrait
{
    /**
     * The day model class.
     *
     * @var class-string<Day>
     */
    private string $dayModel = Calendar::DEFAULT_DAY_MODEL;

    /**
     * The month model class.
     *
     * @var class-string<Month>
     */
    private string $monthModel = Calendar::DEFAULT_MONTH_MODEL;

    /**
     * The week model class.
     *
     * @var class-string<Week>
     */
    private string $weekModel = Calendar::DEFAULT_WEEK_MODEL;

    /**
     * Gets the day model class.
     *
     * @return class-string<Day>
     */
    public function getDayModel(): string
    {
        return $this->dayModel;
    }

    /**
     * Gets the month model class.
     *
     * @return class-string<Month>
     */
    public function getMonthModel(): string
    {
        return $this->monthModel;
    }

    /**
     * Gets the week model class.
     *
     * @return class-string<Week>
     */
    public function getWeekModel(): string
    {
        return $this->weekModel;
    }

    /**
     * Sets the day model class.
     *
     * @param class-string<Day>|null $dayModel the day model class or null to use the default model
     *
     * @throws CalendarException if the day class model does not exist
     */
    public function setDayModel(?string $dayModel): static
    {
        $this->dayModel = $this->checkClass($dayModel, Calendar::DEFAULT_DAY_MODEL);

        return $this;
    }

    /**
     * Sets the month model class.
     *
     * @param class-string<Month>|null $monthModel the month model class or null to use the default model
     *
     * @throws CalendarException if the month class model does not exist
     */
    public function setMonthModel(?string $monthModel): static
    {
        $this->monthModel = $this->checkClass($monthModel, Calendar::DEFAULT_MONTH_MODEL);

        return $this;
    }

    /**
     * Sets the week model class.
     *
     * @param class-string<Week>|null $weekModel the week model class or null to use the default model
     *
     * @throws CalendarException if the week class model does not exist
     */
    public function setWeekModel(?string $weekModel): static
    {
        $this->weekModel = $this->checkClass($weekModel, Calendar::DEFAULT_WEEK_MODEL);

        return $this;
    }

    /**
     * Checks the model class name.
     *
     * @template T of AbstractCalendarItem
     *
     * @param class-string<T>|null $className    the model class name to verify
     * @param class-string<T>      $defaultClass the default model class name to use if the model class name si null
     *
     * @return class-string<T> the class name if no exception
     *
     * @throws CalendarException if the given class name does not exist
     */
    protected function checkClass(?string $className, string $defaultClass): string
    {
        $name = $className ?? $defaultClass;
        if (!\class_exists($name)) {
            throw CalendarException::format('Model class "%s" not found.', $name);
        }

        return $name;
    }
}
