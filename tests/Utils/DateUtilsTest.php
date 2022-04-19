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

use App\Util\DateUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the {@link App\Util\DateUtils} class.
 *
 * @author Laurent Muller
 *
 * @see DateUtils
 */
class DateUtilsTest extends TestCase
{
    public function getCompletYears(): array
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

    public function getMonthNames(): array
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

    public function getShortMonthNames(): array
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

    public function getShortWeekdayNames(): array
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

    public function getWeekdayNames(): array
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

    public function testAddByInterval(): void
    {
        $date = new \DateTime('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::add($date, $interval);
        $this->assertEquals('2020-01-17', $add->format('Y-m-d'));
    }

    public function testAddByString(): void
    {
        $date = new \DateTime('2020-01-10');
        $add = DateUtils::add($date, 'P1W');
        $this->assertEquals('2020-01-17', $add->format('Y-m-d'));
    }

    /**
     *  @dataProvider getCompletYears
     */
    public function testCompletYear(int $value, int $expected, int $change = 1930): void
    {
        $year = DateUtils::completYear($value, $change);
        $this->assertEquals($expected, $year);
    }

    /**
     *  @dataProvider getMonthNames
     */
    public function testMonthNames(string $name, int $index, string $locale = 'fr_CH'): void
    {
        $values = DateUtils::getMonths($locale);
        $this->assertArrayHasKey($index, $values);
        $this->assertSame($name, $values[$index]);
    }

    public function testMonthsCount(): void
    {
        $values = DateUtils::getMonths();
        $this->assertCount(12, $values);
    }

    /**
     *  @dataProvider getShortMonthNames
     */
    public function testShortMonthNames(string $name, int $index, string $locale = 'fr_CH'): void
    {
        $values = DateUtils::getShortMonths($locale);
        $this->assertArrayHasKey($index, $values);
        $this->assertSame($name, $values[$index]);
    }

    public function testShortMonthsCount(): void
    {
        $values = DateUtils::getShortMonths();
        $this->assertCount(12, $values);
    }

    /**
     *  @dataProvider getShortWeekdayNames
     */
    public function testShortWeekdayNames(string $name, int $index, string $firstday = 'sunday', string $locale = 'fr_CH'): void
    {
        $values = DateUtils::getShortWeekdays($firstday, $locale);
        $this->assertArrayHasKey($index, $values);
        $this->assertSame($name, $values[$index]);
    }

    public function testShortWeekdaysCount(): void
    {
        $values = DateUtils::getShortWeekdays();
        $this->assertCount(7, $values);
    }

    public function testSubByInterval(): void
    {
        $date = new \DateTime('2020-01-10');
        $interval = new \DateInterval('P1W');
        $add = DateUtils::sub($date, $interval);
        $this->assertEquals('2020-01-03', $add->format('Y-m-d'));
    }

    public function testSubByString(): void
    {
        $date = new \DateTime('2020-01-10');
        $add = DateUtils::sub($date, 'P1W');
        $this->assertEquals('2020-01-03', $add->format('Y-m-d'));
    }

    /**
     *  @dataProvider getWeekdayNames
     */
    public function testWeekdayNames(string $name, int $index, string $firstday = 'sunday', string $locale = 'fr_CH'): void
    {
        $values = DateUtils::getWeekdays($firstday, $locale);
        $this->assertArrayHasKey($index, $values);
        $this->assertSame($name, $values[$index]);
    }

    public function testWeekdaysCount(): void
    {
        $values = DateUtils::getWeekdays();
        $this->assertCount(7, $values);
    }
}
