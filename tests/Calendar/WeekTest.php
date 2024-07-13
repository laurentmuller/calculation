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

use App\Calendar\CalendarException;
use App\Calendar\Week;
use App\Utils\FormatUtils;

class WeekTest extends CalendarTestCase
{
    public function testFormatKey(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = Week::formatKey(2024, 1);
        self::assertSame('2024.01', $actual);
    }

    public function testGetDay(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $week = $this->createWeek();
        $date = new \DateTime('2024-01-01');
        $actual = $week->getDay($date);
        self::assertNotNull($actual);

        $date = new \DateTime('2025-01-31');
        $actual = $week->getDay($date);
        self::assertNull($actual);
    }

    public function testGetMonths(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $week = $this->createWeek();

        $actual = $week->getMonths();
        self::assertCount(1, $actual);
        self::assertArrayHasKey('2024.01', $actual);

        $expected = $this->createMonth();
        $actual = $actual['2024.01'];
        self::assertSame($expected->getNumber(), $actual->getNumber());
    }

    public function testGetNumber(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = $this->createWeek();
        self::assertSame(1, $actual->getNumber());
    }

    public function testInvalidNumber(): void
    {
        self::expectException(CalendarException::class);
        $calendar = $this->createCalendar();
        new Week($calendar, 0);
    }

    public function testIsCurrent(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $week = $this->createWeek();
        self::assertFalse($week->isCurrent());
    }

    public function testIsInMonth(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $week = $this->createWeek();

        $month = $this->createMonth();
        self::assertTrue($week->isInMonth($month));
        $month = $this->createMonth(2);
        self::assertFalse($week->isInMonth($month));
    }

    public function testJsonSerialize(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $week = $this->createWeek();

        $actual = $week->jsonSerialize();

        self::assertArrayHasKey('week', $actual);
        self::assertArrayHasKey('startDate', $actual);
        self::assertArrayHasKey('endDate', $actual);
        self::assertArrayHasKey('days', $actual);

        self::assertSame(1, $actual['week']);
        self::assertSame('01.01.2024', $actual['startDate']);
        self::assertSame('07.01.2024', $actual['endDate']);
        self::assertIsArray($actual['days']);
        self::assertCount(2, $actual['days']);
    }

    public function testToString(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $week = $this->createWeek();

        $actual = (string) $week;
        self::assertSame('Week(1-2024, 01.01.2024 - 07.01.2024)', $actual);
    }

    protected function createWeek(int $number = 1, int $year = 2024): Week
    {
        $week = parent::createWeek($number, $year);
        $week->addDay($this->createDay());
        $week->addDay($this->createDay('2024-01-07'));

        return $week;
    }
}
