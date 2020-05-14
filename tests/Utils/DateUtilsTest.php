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

namespace App\Tests\Utils;

use App\Utils\DateUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DateUtils.
 *
 * @author Laurent Muller
 *
 * @see DateUtils
 */
class DateUtilsTest extends TestCase
{
    public function testCompletYear(): void
    {
        $this->assertSame(2002, DateUtils::completYear(2));
        $this->assertSame(2002, DateUtils::completYear(2002));
    }

    public function testGetTimeZone(): void
    {
        \date_default_timezone_set('Europe/Zurich');
        $this->assertSame('Europe/Zurich', DateUtils::getTimeZone());
    }
}
