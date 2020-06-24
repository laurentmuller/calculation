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
     * ISO-8601 numeric representation of sunnday.
     */
    public const SUNNDAY = 7;

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
