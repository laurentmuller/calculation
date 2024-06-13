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
use App\Calendar\CalendarException;
use App\Calendar\Day;
use App\Calendar\Month;
use App\Calendar\Week;
use PHPUnit\Framework\TestCase;

abstract class CalendarTestCase extends TestCase
{
    protected function createCalendar(int $year = 2024): Calendar
    {
        try {
            return new Calendar($year);
        } catch (CalendarException $e) {
            self::fail($e->getMessage());
        }
    }

    protected function createDay(string $datetime = '2024-01-01', int $year = 2024): Day
    {
        try {
            $calendar = $this->createCalendar($year);
            $date = new \DateTime($datetime);

            return new Day($calendar, $date);
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }
    }

    protected function createMonth(int $number = 1, int $year = 2024): Month
    {
        $calendar = $this->createCalendar($year);

        try {
            return new Month($calendar, $number);
        } catch (CalendarException $e) {
            self::fail($e->getMessage());
        }
    }

    protected function createWeek(int $number = 1, int $year = 2024): Week
    {
        $calendar = $this->createCalendar($year);

        try {
            return new Week($calendar, $number);
        } catch (CalendarException $e) {
            self::fail($e->getMessage());
        }
    }
}
