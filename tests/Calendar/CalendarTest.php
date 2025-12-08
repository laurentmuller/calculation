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
use App\Calendar\Month;
use App\Calendar\Week;
use App\Utils\FormatUtils;
use Symfony\Component\Clock\DatePoint;

final class CalendarTest extends CalendarTestCase
{
    public function testConstructorEmpty(): void
    {
        $calendar = new Calendar();
        self::assertEmpty($calendar->getDays());
        self::assertEmpty($calendar->getWeeks());
        self::assertEmpty($calendar->getMonths());
    }

    public function testGenerate(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = new Calendar();
        $calendar->generate(2024);

        $start = new DatePoint('2024-01-01');
        $end = new DatePoint('2025-01-05');
        $interval = (int) $start->diff($end)->days + 1;

        self::assertCount($interval, $calendar->getDays());
        self::assertCount(53, $calendar->getWeeks());
        self::assertCount(12, $calendar->getMonths());
    }

    public function testGetCalendar(): void
    {
        $calendar = $this->createCalendar();
        self::assertSame($calendar, $calendar->getCalendar());
    }

    public function testGetDay(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();
        $date = new DatePoint('2024-01-01');
        $actual = $calendar->getDay($date);
        self::assertNotNull($actual);

        $date = new DatePoint('2025-01-31');
        $actual = $calendar->getDay($date);
        self::assertNull($actual);
    }

    public function testGetMonth(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();

        $actual = $calendar->getMonth(1);
        self::assertInstanceOf(Month::class, $actual);

        $actual = $calendar->getMonth(13);
        self::assertNull($actual);

        $date = new DatePoint('2024-01-10');
        $actual = $calendar->getMonth($date);
        self::assertNotNull($actual);

        $date = new DatePoint('2000-01-01');
        $actual = $calendar->getMonth($date);
        self::assertNull($actual);
    }

    public function testGetMonthNames(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();
        $actual = $calendar->getMonthNames();
        self::assertCount(12, $actual);
    }

    public function testGetMonths(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();
        $actual = $calendar->getMonths();
        self::assertCount(12, $actual);
    }

    public function testGetNumber(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();
        self::assertSame(2024, $calendar->getNumber());
    }

    public function testGetShortMonthNames(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();
        $actual = $calendar->getShortMonthNames();
        self::assertCount(12, $actual);
    }

    public function testGetShortWeekNames(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();
        $actual = $calendar->getShortWeekNames();
        self::assertCount(7, $actual);
    }

    public function testGetToday(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();

        $expected = new DatePoint('today');
        $actual = $calendar->getToday();
        self::assertDateTimeEquals($expected, $actual->getDate());
    }

    public function testGetWeek(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();

        $actual = $calendar->getWeek(1);
        self::assertInstanceOf(Week::class, $actual);

        $actual = $calendar->getWeek(100);
        self::assertNull($actual);

        $date = new DatePoint('2024-01-10');
        $actual = $calendar->getWeek($date);
        self::assertNotNull($actual);

        $date = new DatePoint('2000-01-01');
        $actual = $calendar->getWeek($date);
        self::assertNull($actual);
    }

    public function testGetWeekNames(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();
        $actual = $calendar->getWeekNames();
        self::assertCount(7, $actual);
    }

    public function testIsCurrent(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar(2000);
        self::assertFalse($calendar->isCurrent());
    }

    public function testJsonSerialize(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();

        $actual = $calendar->jsonSerialize();

        self::assertArrayHasKey('year', $actual);
        self::assertArrayHasKey('startDate', $actual);
        self::assertArrayHasKey('endDate', $actual);

        self::assertSame(2024, $actual['year']);
        self::assertSame('01.01.2024', $actual['startDate']);
        self::assertSame('05.01.2025', $actual['endDate']);
    }

    public function testToString(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $calendar = $this->createCalendar();

        $actual = (string) $calendar;
        self::assertSame('Calendar(2024, 01.01.2024 - 31.12.2024)', $actual);
    }
}
