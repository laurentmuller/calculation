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
use App\Calendar\Month;
use App\Utils\FormatUtils;
use Symfony\Component\Clock\DatePoint;

final class MonthTest extends CalendarTestCase
{
    public function testFormatKey(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = Month::formatKey(2024, 1);
        self::assertSame('2024.01', $actual);
    }

    public function testGetDay(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();
        $date = new DatePoint('2024-01-01');
        $actual = $month->getDay($date);
        self::assertNotNull($actual);

        $date = new DatePoint('2025-01-31');
        $actual = $month->getDay($date);
        self::assertNull($actual);
    }

    public function testGetName(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();
        $actual = $month->getName();
        self::assertSame('Janvier', $actual);
    }

    public function testGetNumber(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();
        $actual = $month->getNumber();
        self::assertSame(1, $actual);
    }

    public function testGetShortName(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();
        $actual = $month->getShortName();
        self::assertSame('Janv.', $actual);
    }

    public function testGetWeeks(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();

        $actual = $month->getWeeks();
        self::assertCount(5, $actual);
        for ($key = 0, $count = \count($actual); $key < $count; ++$key) {
            self::assertArrayHasKey($key, $actual);
        }
    }

    public function testInvalidNumber(): void
    {
        self::expectException(CalendarException::class);
        self::expectExceptionMessage('The month number 0 is not between 1 and 12 inclusive.');
        $calendar = $this->createCalendar();
        new Month($calendar, 0);
    }

    public function testIsCurrent(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();
        self::assertFalse($month->isCurrent());
    }

    public function testIsInWeek(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();

        $week = $this->createWeek();
        self::assertTrue($month->isInWeek($week));

        $week = $this->createWeek(10);
        self::assertFalse($month->isInWeek($week));
    }

    public function testJsonSerialize(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();

        $actual = $month->jsonSerialize();

        self::assertArrayHasKey('month', $actual);
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('shortName', $actual);
        self::assertArrayHasKey('startDate', $actual);
        self::assertArrayHasKey('endDate', $actual);

        self::assertSame(1, $actual['month']);
        self::assertSame('Janvier', $actual['name']);
        self::assertSame('Janv.', $actual['shortName']);
        self::assertSame('01.01.2024', $actual['startDate']);
        self::assertSame('31.01.2024', $actual['endDate']);
    }

    public function testToString(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $month = $this->createMonth();

        $actual = (string) $month;
        self::assertSame('Month(1.2024, 01.01.2024 - 31.01.2024)', $actual);
    }

    #[\Override]
    protected function createMonth(int $number = 1, int $year = 2024): Month
    {
        $month = parent::createMonth($number, $year);
        $month->addDay($this->createDay());
        $month->addDay($this->createDay('2024-01-31'));

        return $month;
    }
}
