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

namespace App\Tests\Utils;

use App\Util\DateUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link App\Util\DateUtils} class.
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
        $this->assertEquals(2002, DateUtils::completYear(2));
        $this->assertEquals(2002, DateUtils::completYear(2002));
    }

    public function testGetTimeZone(): void
    {
        \date_default_timezone_set('Europe/Zurich');
        $this->assertEquals('Europe/Zurich', DateUtils::getTimeZone());
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
