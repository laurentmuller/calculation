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
 * Months of year constants.
 *
 * @author Laurent Muller
 */
interface MonthsInterface
{
    /**
     * Numeric representation of of April.
     */
    public const APRIL = 4;

    /**
     * Numeric representation of of August.
     */
    public const AUGUST = 8;

    /**
     * Numeric representation of of December.
     */
    public const DECEMBER = 12;

    /**
     * Numeric representation of of February.
     */
    public const FEBRUARY = 2;

    /**
     * Numeric representation of of January.
     */
    public const JANUARY = 1;

    /**
     * Numeric representation of of July.
     */
    public const JULY = 7;

    /**
     * Numeric representation of of June.
     */
    public const JUNE = 6;

    /**
     * Numeric representation of of March.
     */
    public const MARCH = 3;

    /**
     * Numeric representation of of May.
     */
    public const MAY = 5;

    /**
     * The number of months.
     */
    public const MONTHS_COUNT = 12;

    /**
     * Numeric representation of of November.
     */
    public const NOVEMBER = 11;

    /**
     * Numeric representation of of October.
     */
    public const OCTOBER = 10;

    /**
     * Numeric representation of of September.
     */
    public const SEPTEMBER = 9;
}
