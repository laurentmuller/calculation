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
    public const DAYS_COUNT = 7;

    /**
     * ISO-8601 numeric representation of friday.
     */
    public const FRIDAY = 5;

    /**
     * ISO-8601 numeric representation of monday.
     */
    public const MONDAY = 1;

    /**
     * ISO-8601 numeric representation of saturday.
     */
    public const SATURDAY = 6;

    /**
     * ISO-8601 numeric representation of sunday.
     */
    public const SUNDAY = 7;

    /**
     * ISO-8601 numeric representation of thursday.
     */
    public const THURSDAY = 4;

    /**
     * ISO-8601 numeric representation of tuesday.
     */
    public const TUESDAY = 2;

    /**
     * ISO-8601 numeric representation of wednesday.
     */
    public const WEDNESDAY = 3;
}
