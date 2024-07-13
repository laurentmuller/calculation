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

use App\Calendar\CalculationsDay;
use App\Calendar\CalculationsMonth;
use App\Calendar\CalculationsWeek;
use App\Calendar\Calendar;
use App\Calendar\CalendarException;
use App\Entity\Calculation;

class CalculationsTest extends CalendarTestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = $this->createCalendar();
    }

    public function testCalculationDay(): void
    {
        $actual = $this->createCalculationsDay();
        self::assertTrue($actual->isEmpty());
        self::assertCount(0, $actual);
        self::assertSame([], $actual->getCalculations());

        $calculation = new Calculation();
        $actual->addCalculation($calculation);
        self::assertFalse($actual->isEmpty());
        self::assertCount(1, $actual);
        self::assertSame([$calculation], $actual->getCalculations());
    }

    public function testCalculationMonth(): void
    {
        $actual = $this->createCalculationsMonth();
        self::assertTrue($actual->isEmpty());
        self::assertCount(0, $actual);
        self::assertSame([], $actual->getCalculations());

        $calculation = new Calculation();
        $actual->addCalculation($calculation);
        self::assertFalse($actual->isEmpty());
        self::assertCount(1, $actual);
        self::assertSame([$calculation], $actual->getCalculations());
    }

    public function testCalculationsWeek(): void
    {
        $actual = $this->createCalculationsWeek();
        self::assertTrue($actual->isEmpty());
        self::assertCount(0, $actual);
        self::assertSame([], $actual->getCalculations());

        $calculation = new Calculation();
        $actual->addCalculation($calculation);
        self::assertFalse($actual->isEmpty());
        self::assertCount(1, $actual);
        self::assertSame([$calculation], $actual->getCalculations());
    }

    private function createCalculationsDay(): CalculationsDay
    {
        try {
            $date = new \DateTime('2024-01-01');

            return new CalculationsDay($this->calendar, $date);
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }
    }

    private function createCalculationsMonth(): CalculationsMonth
    {
        try {
            return new CalculationsMonth($this->calendar, 1);
        } catch (CalendarException $e) {
            self::fail($e->getMessage());
        }
    }

    private function createCalculationsWeek(): CalculationsWeek
    {
        try {
            return new CalculationsWeek($this->calendar, 1);
        } catch (CalendarException $e) {
            self::fail($e->getMessage());
        }
    }
}
