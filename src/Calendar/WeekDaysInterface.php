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
 * Days of week constants.
 */
interface WeekDaysInterface
{
    /**
     * The number of week days.
     */
    public const int DAYS_COUNT = 7;

    /**
     * ISO-8601 numeric representation of friday.
     */
    public const int FRIDAY = 5;

    /**
     * ISO-8601 numeric representation of monday.
     */
    public const int MONDAY = 1;

    /**
     * ISO-8601 numeric representation of saturday.
     */
    public const int SATURDAY = 6;

    /**
     * ISO-8601 numeric representation of sunday.
     */
    public const int SUNDAY = 7;

    /**
     * ISO-8601 numeric representation of thursday.
     */
    public const int THURSDAY = 4;

    /**
     * ISO-8601 numeric representation of tuesday.
     */
    public const int TUESDAY = 2;

    /**
     * ISO-8601 numeric representation of wednesday.
     */
    public const int WEDNESDAY = 3;
}
