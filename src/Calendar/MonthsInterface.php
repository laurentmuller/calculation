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
 * Months of year constants.
 */
interface MonthsInterface
{
    /**
     * Numeric representation of April.
     */
    public const int APRIL = 4;

    /**
     * Numeric representation of August.
     */
    public const int AUGUST = 8;

    /**
     * Numeric representation of December.
     */
    public const int DECEMBER = 12;

    /**
     * Numeric representation of February.
     */
    public const int FEBRUARY = 2;

    /**
     * Numeric representation of January.
     */
    public const int JANUARY = 1;

    /**
     * Numeric representation of July.
     */
    public const int JULY = 7;

    /**
     * Numeric representation of June.
     */
    public const int JUNE = 6;

    /**
     * Numeric representation of March.
     */
    public const int MARCH = 3;

    /**
     * Numeric representation of May.
     */
    public const int MAY = 5;

    /**
     * The number of months.
     */
    public const int MONTHS_COUNT = 12;

    /**
     * Numeric representation of November.
     */
    public const int NOVEMBER = 11;

    /**
     * Numeric representation of October.
     */
    public const int OCTOBER = 10;

    /**
     * Numeric representation of September.
     */
    public const int SEPTEMBER = 9;
}
