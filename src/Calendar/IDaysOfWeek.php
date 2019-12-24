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
 * Days of week constants.
 *
 * @author Laurent Muller
 */
interface IDaysOfWeek
{
    /**
     * The number of week days.
     */
    const DAYS_COUNT = 7;

    /**
     * ISO-8601 numeric representation of friday.
     */
    const FRIDAY = 5;

    /**
     * ISO-8601 numeric representation of monday.
     */
    const MONDAY = 1;

    /**
     * ISO-8601 numeric representation of saturday.
     */
    const SATURDAY = 6;

    /**
     * ISO-8601 numeric representation of sunnday.
     */
    const SUNNDAY = 7;

    /**
     * ISO-8601 numeric representation of thursday.
     */
    const THURSDAY = 4;

    /**
     * ISO-8601 numeric representation of tuesday.
     */
    const TUESDAY = 2;

    /**
     * ISO-8601 numeric representation of wednesday.
     */
    const WEDNESDAY = 3;
}
