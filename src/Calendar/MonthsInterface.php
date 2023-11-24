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
     *
     * @psalm-api
     */
    public const APRIL = 4;

    /**
     * Numeric representation of August.
     *
     * @psalm-api
     */
    public const AUGUST = 8;

    /**
     * Numeric representation of December.
     *
     * @psalm-api
     */
    public const DECEMBER = 12;

    /**
     * Numeric representation of February.
     *
     * @psalm-api
     */
    public const FEBRUARY = 2;

    /**
     * Numeric representation of January.
     *
     * @psalm-api
     */
    public const JANUARY = 1;

    /**
     * Numeric representation of July.
     *
     * @psalm-api
     */
    public const JULY = 7;

    /**
     * Numeric representation of June.
     *
     * @psalm-api
     */
    public const JUNE = 6;

    /**
     * Numeric representation of March.
     *
     * @psalm-api
     */
    public const MARCH = 3;

    /**
     * Numeric representation of May.
     *
     * @psalm-api
     */
    public const MAY = 5;

    /**
     * The number of months.
     *
     * @psalm-api
     */
    public const MONTHS_COUNT = 12;

    /**
     * Numeric representation of November.
     *
     * @psalm-api
     */
    public const NOVEMBER = 11;

    /**
     * Numeric representation of October.
     *
     * @psalm-api
     */
    public const OCTOBER = 10;

    /**
     * Numeric representation of September.
     *
     * @psalm-api
     */
    public const SEPTEMBER = 9;
}
