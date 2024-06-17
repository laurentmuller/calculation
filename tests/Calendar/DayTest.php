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

use App\Calendar\AbstractCalendarItem;
use App\Calendar\Day;
use App\Utils\FormatUtils;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Day::class)]
#[CoversClass(AbstractCalendarItem::class)]
class DayTest extends CalendarTestCase
{
    public function testFormat(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->format('d/m/Y');
        self::assertSame('01/01/2024', $actual);
    }

    public function testGetDate(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $expected = new \DateTime('2024-01-01');
        $day = $this->createDay();
        $actual = $day->getDate();
        self::assertSameDate($expected, $actual);
    }

    public function testGetDayOfYear(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getDayOfYear();
        self::assertSame(0, $actual);
    }

    public function testGetMonth(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getMonth();
        self::assertSame(1, $actual);
    }

    public function testGetName(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getName();
        self::assertSame('Lundi', $actual);
    }

    public function testGetNumber(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getNumber();
        self::assertSame(1, $actual);
    }

    public function testGetShortName(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getShortName();
        self::assertSame('Lun.', $actual);
    }

    public function testGetTimestamp(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getTimestamp();
        $expected = new \DateTime('2024-01-01');
        self::assertSameDate($expected, $actual);
    }

    public function testGetWeek(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getWeek();
        self::assertSame(1, $actual);
    }

    public function testGetWeekDay(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getWeekDay();
        self::assertSame(1, $actual);
    }

    public function testGetYear(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->getYear();
        self::assertSame(2024, $actual);
    }

    public function testIsCurrent(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        self::assertFalse($day->isCurrent());
    }

    public function testIsFirstInWeek(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        self::assertTrue($day->isFirstInWeek());
    }

    public function testIsInMonth(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $month = $this->createMonth();
        self::assertTrue($day->isInMonth($month));
        $month = $this->createMonth(2);
        self::assertFalse($day->isInMonth($month));
    }

    public function testIsInWeek(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $week = $this->createWeek();
        self::assertTrue($day->isInWeek($week));
        $week = $this->createWeek(2);
        self::assertFalse($day->isInWeek($week));
    }

    public function testIsInYear(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        self::assertTrue($day->isInYear(2024));
        self::assertFalse($day->isInYear(2022));
    }

    public function testIsLastInWeek(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        self::assertFalse($day->isLastInWeek());
    }

    public function testIsWeekend(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        self::assertFalse($day->isWeekend());
    }

    public function testJsonSerialize(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = $day->jsonSerialize();

        self::assertArrayHasKey('day', $actual);
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('shortName', $actual);
        self::assertArrayHasKey('date', $actual);

        self::assertSame(1, $actual['day']);
        self::assertSame('Lundi', $actual['name']);
        self::assertSame('Lun.', $actual['shortName']);
        self::assertSame('01.01.2024', $actual['date']);
    }

    public function testToString(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $day = $this->createDay();
        $actual = (string) $day;
        self::assertSame('Day(01.01.2024)', $actual);
    }
}
