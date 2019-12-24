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
interface IMonthsOfYear
{
    /**
     * Numeric representation of of April.
     */
    const APRIL = 4;

    /**
     * Numeric representation of of August.
     */
    const AUGUST = 8;

    /**
     * Numeric representation of of December.
     */
    const DECEMBER = 12;

    /**
     * Numeric representation of of February.
     */
    const FEBRUARY = 2;

    /**
     * Numeric representation of of January.
     */
    const JANUARY = 1;

    /**
     * Numeric representation of of July.
     */
    const JULY = 7;

    /**
     * Numeric representation of of June.
     */
    const JUNE = 6;

    /**
     * Numeric representation of of March.
     */
    const MARCH = 3;

    /**
     * Numeric representation of of May.
     */
    const MAY = 5;

    /**
     * The number of months.
     */
    const MONTHS_COUNT = 12;

    /**
     * Numeric representation of of November.
     */
    const NOVEMBER = 11;

    /**
     * Numeric representation of of October.
     */
    const OCTOBER = 10;

    /**
     * Numeric representation of of September.
     */
    const SEPTEMBER = 9;
}
