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

namespace App\Tests\Calendar;

use App\Calendar\Calendar;
use App\Calendar\Day;
use App\Calendar\Month;
use App\Calendar\Week;
use App\Tests\DateAssertTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

abstract class CalendarTestCase extends TestCase
{
    use DateAssertTrait;

    protected function createCalendar(int $year = 2024): Calendar
    {
        return new Calendar($year);
    }

    protected function createDay(string $datetime = '2024-01-01', int $year = 2024): Day
    {
        $calendar = $this->createCalendar($year);
        $date = new DatePoint($datetime);

        return new Day($calendar, $date);
    }

    protected function createMonth(int $number = 1, int $year = 2024): Month
    {
        $calendar = $this->createCalendar($year);

        return new Month($calendar, $number);
    }

    protected function createWeek(int $number = 1, int $year = 2024): Week
    {
        $calendar = $this->createCalendar($year);

        return new Week($calendar, $number);
    }
}
