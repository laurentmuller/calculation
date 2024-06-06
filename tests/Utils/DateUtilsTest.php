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
use App\Utils\FormatUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateUtils::class)]
class DateUtilsTest extends TestCase
{
    protected function setUp(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        \setlocale(\LC_ALL, FormatUtils::DEFAULT_LOCALE);
    }

    public static function getCompletYears(): \Iterator
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

    public static function getFormatFormDate(): \Iterator
    {
        yield [null, null];
        yield [new \DateTime('2022-1-1'), '2022-01-01'];
        yield [new \DateTime('2022-9-9'), '2022-09-09'];
        yield [new \DateTime('2022-01-01'), '2022-01-01'];
        yield [new \DateTime('2022-12-31'), '2022-12-31'];
        yield [new \DateTime('22-12-31'), '2022-12-31'];
        yield [new \DateTime('22-2-1'), '2022-02-01'];
    }

    public static function getMonthNames(): \Iterator
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

    public static function getShortMonthNames(): \Iterator
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

    public static function getShortWeekdayNames(): \Iterator
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

    public static function getWeekdayNames(): \Iterator
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

    #[DataProvider('getCompletYears')]
    public function testCompletYear(int $value, int $expected, int $change = 1930): void
    {
        $year = DateUtils::completYear($value, $change);
        self::assertSame($expected, $year);
    }

    #[DataProvider('getFormatFormDate')]
    public function testFormatFormDate(?\DateTimeInterface $date, ?string $expected): void
    {
        $actual = DateUtils::formatFormDate($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getDays')]
    public function testGetDay(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getDay($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getMonths')]
    public function testGetMonth(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getMonth($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getWeeks')]
    public function testGetWeek(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getWeek($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getYears')]
    public function testGetYear(\DateTimeInterface $date, int $expected): void
    {
        $actual = DateUtils::getYear($date);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getMonthNames')]
    public function testMonthNames(string $name, int $index): void
    {
        if (FormatUtils::DEFAULT_LOCALE !== \Locale::getDefault()) {
            self::markTestSkipped('The default locale is not set to "fr_CH".');
        }

        $values = DateUtils::getMonths();
        self::assertArrayHasKey($index, $values);
        self::assertSame($name, $values[$index]);
    }

    public function testMonthsCount(): void
    {
        $values = DateUtils::getMonths();
        self::assertCount(12, $values);
    }

    #[DataProvider('getRemoveTimes')]
    public function testRemoveTime(\DateTime|\DateTimeImmutable $date, \DateTimeInterface $expected): void
    {
        $value = DateUtils::removeTime($date);
        self::assertSame($expected->getTimestamp(), $value->getTimestamp());
    }

    #[DataProvider('getShortMonthNames')]
    public function testShortMonthNames(string $name, int $index): void
    {
        if (FormatUtils::DEFAULT_LOCALE !== \Locale::getDefault()) {
            self::markTestSkipped('The default locale is not set to "fr_CH".');
        }

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
        if (FormatUtils::DEFAULT_LOCALE !== \Locale::getDefault()) {
            self::markTestSkipped('The default locale is not set to "fr_CH".');
        }

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

    #[DataProvider('getWeekdayNames')]
    public function testWeekdayNames(string $name, int $index, string $firstDay = 'sunday'): void
    {
        if (FormatUtils::DEFAULT_LOCALE !== \Locale::getDefault()) {
            self::markTestSkipped('The default locale is not set to "fr_CH".');
        }

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
