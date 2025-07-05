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

namespace App\Tests\Utils;

use App\Tests\DateAssertTrait;
use App\Tests\PrivateInstanceTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class DateUtilsTest extends TestCase
{
    use DateAssertTrait;
    use PrivateInstanceTrait;

    #[\Override]
    protected function setUp(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
    }

    /**
     * @phpstan-return \Generator<int, array{0: int, 1: int,2?: 1931}>
     */
    public static function getCompletYears(): \Generator
    {
        yield [29, 2029];
        yield [30, 2030];
        yield [31, 1931];
        yield [32, 1932];
        yield [2, 2002];
        yield [70, 1970];
        yield [1932, 1932];
        yield [2002, 2002];
        yield [0, 2000];
        yield [90, 1990];
        yield [99, 1999];
        yield [30, 2030, 1931];
        yield [31, 2031, 1931];
    }

    /**
     * @phpstan-return \Generator<int, array{DatePoint, int}>
     */
    public static function getDays(): \Generator
    {
        // today
        $date = new DatePoint();
        $day = (int) $date->format('j');
        yield [$date, $day];

        foreach (\range(1, 31) as $index) {
            yield [new DatePoint("2022-01-$index"), $index];
        }
    }

    /**
     * @phpstan-return \Generator<int, array{?DatePoint, ?string}>
     */
    public static function getFormatFormDate(): \Generator
    {
        yield [null, null];
        yield [new DatePoint('2022-1-1'), '2022-01-01'];
        yield [new DatePoint('2022-9-9'), '2022-09-09'];
        yield [new DatePoint('2022-01-01'), '2022-01-01'];
        yield [new DatePoint('2022-12-31'), '2022-12-31'];
        yield [new DatePoint('22-12-31'), '2022-12-31'];
        yield [new DatePoint('22-2-1'), '2022-02-01'];
    }

    /**
     * @phpstan-return \Generator<int, array{DatePoint, string, string}>
     */
    public static function getModifies(): \Generator
    {
        $date = new DatePoint('2024-01-10');

        yield [$date, '+1 day', '2024-01-11'];
        yield [$date, '-1 day', '2024-01-09'];

        yield [$date, '+1 month', '2024-02-10'];
        yield [$date, '-1 month', '2023-12-10'];

        yield [$date, '+1 year', '2025-01-10'];
        yield [$date, '-1 year', '2023-01-10'];

        yield [$date, 'July 1st, 2023', '2023-07-01'];
    }

    /**
     * @phpstan-return \Generator<int, array{string, int}>
     */
    public static function getMonthNames(): \Generator
    {
        yield ['Janvier', 1];
        yield ['Février', 2];
        yield ['Mars', 3];
        yield ['Avril', 4];
        yield ['Mai', 5];
        yield ['Juin', 6];
        yield ['Juillet', 7];
        yield ['Août', 8];
        yield ['Septembre', 9];
        yield ['Octobre', 10];
        yield ['Novembre', 11];
        yield ['Décembre', 12];
    }

    /**
     * @phpstan-return \Generator<int, array{DatePoint, int}>
     */
    public static function getMonths(): \Generator
    {
        // today
        $date = new DatePoint();
        $month = (int) $date->format('n');
        yield [$date, $month];

        foreach (\range(1, 12) as $index) {
            yield [new DatePoint("2015-$index-01"), $index];
        }
    }

    /**
     * @phpstan-return \Generator<int, array{DatePoint, DatePoint}>
     */
    public static function getRemoveTimes(): \Generator
    {
        $format = 'Y-m-d H:i:s';
        $expected = DatePoint::createFromFormat($format, '2013-03-15 00:00:00');

        $date = DatePoint::createFromFormat($format, '2013-03-15 00:00:00');
        yield [$date, $expected];

        $date = DatePoint::createFromFormat($format, '2013-03-15 01:02:03');
        yield [$date, $expected];

        $date = DatePoint::createFromFormat($format, '2013-03-15 23:59:59');
        yield [$date, $expected];

        $format = 'Y-m-d H:i:s.v';
        $expected = DatePoint::createFromFormat($format, '2013-03-15 00:00:00.123');
        yield [$date, $expected];
    }

    /**
     * @phpstan-return \Generator<int, array{string, int}>
     */
    public static function getShortMonthNames(): \Generator
    {
        yield ['Janv.', 1];
        yield ['Févr.', 2];
        yield ['Mars', 3];
        yield ['Avr.', 4];
        yield ['Mai', 5];
        yield ['Juin', 6];
        yield ['Juil.', 7];
        yield ['Août', 8];
        yield ['Sept.', 9];
        yield ['Oct.', 10];
        yield ['Nov.', 11];
        yield ['Déc.', 12];
    }

    /**
     * @phpstan-return \Generator<int, array{0: string, 1: int, 2?: 'monday'}>
     */
    public static function getShortWeekdayNames(): \Generator
    {
        // default (sunday)
        yield ['Dim.', 1];
        yield ['Lun.', 2];
        yield ['Mar.', 3];
        yield ['Mer.', 4];
        yield ['Jeu.', 5];
        yield ['Ven.', 6];
        yield ['Sam.', 7];
        // monday
        yield ['Lun.', 1, 'monday'];
        yield ['Mar.', 2, 'monday'];
        yield ['Mer.', 3, 'monday'];
        yield ['Jeu.', 4, 'monday'];
        yield ['Ven.', 5, 'monday'];
        yield ['Sam.', 6, 'monday'];
        yield ['Dim.', 7, 'monday'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: string, 1: int, 2?: 'monday'}>
     */
    public static function getWeekdayNames(): \Generator
    {
        // default (sunday)
        yield ['Dimanche', 1];
        yield ['Lundi', 2];
        yield ['Mardi', 3];
        yield ['Mercredi', 4];
        yield ['Jeudi', 5];
        yield ['Vendredi', 6];
        yield ['Samedi', 7];
        // monday
        yield ['Lundi', 1, 'monday'];
        yield ['Mardi', 2, 'monday'];
        yield ['Mercredi', 3, 'monday'];
        yield ['Jeudi', 4, 'monday'];
        yield ['Vendredi', 5, 'monday'];
        yield ['Samedi', 6, 'monday'];
        yield ['Dimanche', 7, 'monday'];
    }

    /**
     * @phpstan-return \Generator<int, array{DatePoint, int}>
     */
    public static function getWeeks(): \Generator
    {
        // today
        $date = new DatePoint();
        $week = (int) $date->format('W');
        yield [$date, $week];

        yield [new DatePoint('2023-04-14'), 15];
        yield [new DatePoint('2023-04-21'), 16];
        yield [new DatePoint('2023-04-28'), 17];
    }

    /**
     * @phpstan-return \Generator<int, array{DatePoint, int}>
     */
    public static function getYears(): \Generator
    {
        // today
        $date = new DatePoint();
        $year = (int) $date->format('Y');
        yield [$date, $year];

        foreach (\range(2000, 2012) as $index) {
            yield [new DatePoint("$index-01-01"), $index];
        }
    }

    /**
     * @throws \Exception
     */
    public function testAddByInterval(): void
    {
        $date = new DatePoint('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::add($date, $interval);
        self::assertSame('2020-01-17', $add->format('Y-m-d'));
    }

    /**
     * @throws \Exception
     */
    public function testAddByString(): void
    {
        $date = new DatePoint('2020-01-10');
        $add = DateUtils::add($date, 'P1W');
        self::assertSame('2020-01-17', $add->format('Y-m-d'));
    }

    #[DataProvider('getCompletYears')]
    public function testCompletYear(int $value, int $expected, int $change = 1930): void
    {
        $year = DateUtils::completYear($value, $change);
        self::assertSame($expected, $year);
    }

    public function testCompletYearWithDefault(): void
    {
        $expected = (int) (new DatePoint())->format('Y');
        $actual = DateUtils::completYear();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getFormatFormDate')]
    public function testFormatFormDate(?DatePoint $date, ?string $expected): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = DateUtils::formatFormDate($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getDays')]
    public function testGetDay(DatePoint $date, int $expected): void
    {
        $actual = DateUtils::getDay($date);
        self::assertSame($expected, $actual);
    }

    public function testGetDefaultDay(): void
    {
        $actual = DateUtils::getDay();
        $expected = (int) (new DatePoint())->format('j');
        self::assertSame($expected, $actual);
    }

    public function testGetDefaultMonth(): void
    {
        $actual = DateUtils::getMonth();
        $expected = (int) (new DatePoint())->format('n');
        self::assertSame($expected, $actual);
    }

    public function testGetDefaultWeek(): void
    {
        $actual = DateUtils::getWeek();
        $expected = (int) (new DatePoint())->format('W');
        self::assertSame($expected, $actual);
    }

    public function testGetDefaultYear(): void
    {
        $actual = DateUtils::getYear();
        $expected = (int) (new DatePoint())->format('Y');
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getMonths')]
    public function testGetMonth(DatePoint $date, int $expected): void
    {
        $actual = DateUtils::getMonth($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getWeeks')]
    public function testGetWeek(DatePoint $date, int $expected): void
    {
        $actual = DateUtils::getWeek($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getYears')]
    public function testGetYear(DatePoint $date, int $expected): void
    {
        $actual = DateUtils::getYear($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getModifies')]
    public function testModify(DatePoint $date, string $modifier, string $expected): void
    {
        $date = DateUtils::modify($date, $modifier);
        $actual = $date->format('Y-m-d');
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getMonthNames')]
    public function testMonthNames(string $name, int $index): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $values = DateUtils::getMonths();
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testMonthsCount(): void
    {
        $values = DateUtils::getMonths();
        self::assertCount(12, $values);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPrivateInstance(): void
    {
        self::assertPrivateInstance(DateUtils::class);
    }

    #[DataProvider('getRemoveTimes')]
    public function testRemoveTime(DatePoint $date, DatePoint $expected): void
    {
        $actual = DateUtils::removeTime($date);
        self::assertTimestampEquals($expected, $actual);
    }

    #[DataProvider('getShortMonthNames')]
    public function testShortMonthNames(string $name, int $index): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $values = DateUtils::getShortMonths();
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testShortMonthsCount(): void
    {
        $values = DateUtils::getShortMonths();
        self::assertCount(12, $values);
    }

    #[DataProvider('getShortWeekdayNames')]
    public function testShortWeekdayNames(string $name, int $index, string $firstDay = 'sunday'): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $values = DateUtils::getShortWeekdays($firstDay);
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testShortWeekdaysCount(): void
    {
        $values = DateUtils::getShortWeekdays();
        self::assertCount(7, $values);
    }

    /**
     * @throws \Exception
     */
    public function testSubByInterval(): void
    {
        $date = new DatePoint('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::sub($date, $interval);
        self::assertSame('2020-01-03', $add->format('Y-m-d'));
    }

    /**
     * @throws \Exception
     */
    public function testSubByString(): void
    {
        $date = new DatePoint('2020-01-10');
        $add = DateUtils::sub($date, 'P1W');
        self::assertSame('2020-01-03', $add->format('Y-m-d'));
    }

    public function testToDatePoint(): void
    {
        $date = new \DateTime();
        $actual = DateUtils::toDatePoint($date);
        self::assertTimestampEquals($date, $actual);

        $date = new DatePoint();
        $actual = DateUtils::toDatePoint($date);
        self::assertTimestampEquals($date, $actual);
    }

    #[DataProvider('getWeekdayNames')]
    public function testWeekdayNames(string $name, int $index, string $firstDay = 'sunday'): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $values = DateUtils::getWeekdays($firstDay);
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testWeekdaysCount(): void
    {
        $values = DateUtils::getWeekdays();
        self::assertCount(7, $values);
    }
}
