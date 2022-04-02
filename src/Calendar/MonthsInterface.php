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

/**
 * Months of year constants.
 *
 * @author Laurent Muller
 */
interface MonthsInterface
{
    /**
     * Numeric representation of April.
     */
    public const APRIL = 4;

    /**
     * Numeric representation of August.
     */
    public const AUGUST = 8;

    /**
     * Numeric representation of December.
     */
    public const DECEMBER = 12;

    /**
     * Numeric representation of February.
     */
    public const FEBRUARY = 2;

    /**
     * Numeric representation of January.
     */
    public const JANUARY = 1;

    /**
     * Numeric representation of July.
     */
    public const JULY = 7;

    /**
     * Numeric representation of June.
     */
    public const JUNE = 6;

    /**
     * Numeric representation of March.
     */
    public const MARCH = 3;

    /**
     * Numeric representation of May.
     */
    public const MAY = 5;

    /**
     * The number of months.
     */
    public const MONTHS_COUNT = 12;

    /**
     * Numeric representation of November.
     */
    public const NOVEMBER = 11;

    /**
     * Numeric representation of October.
     */
    public const OCTOBER = 10;

    /**
     * Numeric representation of September.
     */
    public const SEPTEMBER = 9;
}
