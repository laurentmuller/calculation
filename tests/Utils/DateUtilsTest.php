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

use App\Util\DateUtils;
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
    public function testAddByInterval(): void
    {
        $date = new \DateTime('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::add($date, $interval);
        $this->assertEquals('2020-01-17', $add->format('Y-m-d'));
    }

    public function testAddByString(): void
    {
        $date = new \DateTime('2020-01-10');
        $add = DateUtils::add($date, 'P1W');
        $this->assertEquals('2020-01-17', $add->format('Y-m-d'));
    }

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

    public function testMonths(): void
    {
        $months = DateUtils::getMonths();
        $this->assertCount(12, $months);
    }

    public function testShortMonths(): void
    {
        $months = DateUtils::getShortMonths();
        $this->assertCount(12, $months);
    }

    public function testShortWeekdays(): void
    {
        $months = DateUtils::getShortWeekdays();
        $this->assertCount(7, $months);
    }

    public function testSubByInterval(): void
    {
        $date = new \DateTime('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::sub($date, $interval);
        $this->assertEquals('2020-01-03', $add->format('Y-m-d'));
    }

    public function testSubByString(): void
    {
        $date = new \DateTime('2020-01-10');
        $add = DateUtils::sub($date, 'P1W');
        $this->assertEquals('2020-01-03', $add->format('Y-m-d'));
    }

    public function testWeekdays(): void
    {
        $months = DateUtils::getWeekdays();
        $this->assertCount(7, $months);
    }
}
