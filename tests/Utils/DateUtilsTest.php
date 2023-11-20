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

use App\Utils\DateUtils;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(DateUtils::class)]
class DateUtilsTest extends TestCase
{
    public static function getCompletYears(): array
    {
        return [
            [29, 2029],
            [30, 2030],
            [31, 1931],
            [32, 1932],
            [2, 2002],
            [70, 1970],
            [1932, 1932],
            [2002, 2002],

            [0, 2000],
            [90, 1990],
            [99, 1999],

            [30, 2030, 1931],
            [31, 2031, 1931],
        ];
    }

    public static function getDays(): \Generator
    {
        // today
        $date = new \DateTime();
        $day = (int) $date->format('j');
        yield [$date, $day];

        foreach (\range(1, 31) as $index) {
            yield [new \DateTime("2022-01-$index"), $index];
        }
    }

    /**
     * @return array<array{0:\DateTimeInterface|null, 1: string|null}>
     */
    public static function getFormatFormDate(): array
    {
        return [
            [null, null],
            [new \DateTime('2022-1-1'), '2022-01-01'],
            [new \DateTime('2022-9-9'), '2022-09-09'],
            [new \DateTime('2022-01-01'), '2022-01-01'],
            [new \DateTime('2022-12-31'), '2022-12-31'],
            [new \DateTime('22-12-31'), '2022-12-31'],
            [new \DateTime('22-2-1'), '2022-02-01'],
        ];
    }

    /**
     * @return array<array{0:string, 1: int}>
     */
    public static function getMonthNames(): array
    {
        return [
            ['Janvier', 1],
            ['Février', 2],
            ['Mars', 3],
            ['Avril', 4],
            ['Mai', 5],
            ['Juin', 6],
            ['Juillet', 7],
            ['Août', 8],
            ['Septembre', 9],
            ['Octobre', 10],
            ['Novembre', 11],
            ['Décembre', 12],
        ];
    }

    public static function getMonths(): \Generator
    {
        // today
        $date = new \DateTime();
        $month = (int) $date->format('n');
        yield [$date, $month];

        foreach (\range(1, 12) as $index) {
            yield [new \DateTime("2015-$index-01"), $index];
        }
    }

    public static function getRemoveTimes(): \Generator
    {
        $format = 'Y-m-d H:i:s';
        $expected = \DateTimeImmutable::createFromFormat($format, '2013-03-15 00:00:00');

        $date = \DateTime::createFromFormat($format, '2013-03-15 00:00:00');
        yield [$date, $expected];

        $date = \DateTime::createFromFormat($format, '2013-03-15 01:02:03');
        yield [$date, $expected];

        $date = \DateTime::createFromFormat($format, '2013-03-15 23:59:59');
        yield [$date, $expected];
    }

    /**
     * @return array<array{0:string, 1: int}>
     */
    public static function getShortMonthNames(): array
    {
        return [
            ['Janv.', 1],
            ['Févr.', 2],
            ['Mars', 3],
            ['Avr.', 4],
            ['Mai', 5],
            ['Juin', 6],
            ['Juil.', 7],
            ['Août', 8],
            ['Sept.', 9],
            ['Oct.', 10],
            ['Nov.', 11],
            ['Déc.', 12],
        ];
    }

    public static function getShortWeekdayNames(): array
    {
        return [
            // default (sunday)
            ['Dim.', 1],
            ['Lun.', 2],
            ['Mar.', 3],
            ['Mer.', 4],
            ['Jeu.', 5],
            ['Ven.', 6],
            ['Sam.', 7],

            // monday
            ['Lun.', 1, 'monday'],
            ['Mar.', 2, 'monday'],
            ['Mer.', 3, 'monday'],
            ['Jeu.', 4, 'monday'],
            ['Ven.', 5, 'monday'],
            ['Sam.', 6, 'monday'],
            ['Dim.', 7, 'monday'],
        ];
    }

    /**
     * @return array<array{0:string, 1: int, 2?: string}>
     */
    public static function getWeekdayNames(): array
    {
        return [
            // default (sunday)
            ['Dimanche', 1],
            ['Lundi', 2],
            ['Mardi', 3],
            ['Mercredi', 4],
            ['Jeudi', 5],
            ['Vendredi', 6],
            ['Samedi', 7],

            // monday
            ['Lundi', 1, 'monday'],
            ['Mardi', 2, 'monday'],
            ['Mercredi', 3, 'monday'],
            ['Jeudi', 4, 'monday'],
            ['Vendredi', 5, 'monday'],
            ['Samedi', 6, 'monday'],
            ['Dimanche', 7, 'monday'],
        ];
    }

    public static function getWeeks(): \Generator
    {
        // today
        $date = new \DateTime();
        $week = (int) $date->format('W');
        yield [$date, $week];

        yield [new \DateTime('2023-04-14'), 15];
        yield [new \DateTime('2023-04-21'), 16];
        yield [new \DateTime('2023-04-28'), 17];
    }

    public static function getYears(): \Generator
    {
        // today
        $date = new \DateTime();
        $year = (int) $date->format('Y');
        yield [$date, $year];

        foreach (\range(2000, 2012) as $index) {
            yield [new \DateTime("$index-01-01"), $index];
        }
    }

    /**
     * @throws \Exception
     */
    public function testAddByInterval(): void
    {
        $date = new \DateTime('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::add($date, $interval);
        self::assertSame('2020-01-17', $add->format('Y-m-d'));
    }

    /**
     * @throws \Exception
     */
    public function testAddByString(): void
    {
        $date = new \DateTime('2020-01-10');
        $add = DateUtils::add($date, 'P1W');
        self::assertSame('2020-01-17', $add->format('Y-m-d'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getCompletYears')]
    public function testCompletYear(int $value, int $expected, int $change = 1930): void
    {
        $year = DateUtils::completYear($value, $change);
        self::assertSame($expected, $year);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getFormatFormDate')]
    public function testFormatFormDate(?\DateTimeInterface $date, ?string $expected): void
    {
        $actual = DateUtils::formatFormDate($date);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDays')]
    public function testGetDay(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getDay($date);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMonths')]
    public function testGetMonth(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getMonth($date);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getWeeks')]
    public function testGetWeek(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getWeek($date);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getYears')]
    public function testGetYear(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getYear($date);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMonthNames')]
    public function testMonthNames(string $name, int $index): void
    {
        \setlocale(\LC_TIME, 'fr_CH');
        $values = DateUtils::getMonths();
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testMonthsCount(): void
    {
        $values = DateUtils::getMonths();
        self::assertCount(12, $values);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRemoveTimes')]
    public function testRemoveTime(\DateTime|\DateTimeImmutable $date, \DateTimeInterface $expected): void
    {
        $value = DateUtils::removeTime($date);
        self::assertSame($expected->getTimestamp(), $value->getTimestamp());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getShortMonthNames')]
    public function testShortMonthNames(string $name, int $index): void
    {
        \setlocale(\LC_TIME, 'fr_CH');
        $values = DateUtils::getShortMonths();
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testShortMonthsCount(): void
    {
        \setlocale(\LC_TIME, 'fr_CH');
        $values = DateUtils::getShortMonths();
        self::assertCount(12, $values);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getShortWeekdayNames')]
    public function testShortWeekdayNames(string $name, int $index, string $firstDay = 'sunday'): void
    {
        \setlocale(\LC_TIME, 'fr_CH');
        $values = DateUtils::getShortWeekdays($firstDay);
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testShortWeekdaysCount(): void
    {
        \setlocale(\LC_TIME, 'fr_CH');
        $values = DateUtils::getShortWeekdays();
        self::assertCount(7, $values);
    }

    /**
     * @throws \Exception
     */
    public function testSubByInterval(): void
    {
        $date = new \DateTime('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::sub($date, $interval);
        self::assertSame('2020-01-03', $add->format('Y-m-d'));
    }

    /**
     * @throws \Exception
     */
    public function testSubByString(): void
    {
        $date = new \DateTime('2020-01-10');
        $add = DateUtils::sub($date, 'P1W');
        self::assertSame('2020-01-03', $add->format('Y-m-d'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getWeekdayNames')]
    public function testWeekdayNames(string $name, int $index, string $firstDay = 'sunday'): void
    {
        \setlocale(\LC_ALL, 'fr_CH');
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
