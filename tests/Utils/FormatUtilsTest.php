<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Util\FormatUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the {@link App\Util\FormatUtils} class.
 *
 * @author Laurent Muller
 */
class FormatUtilsTest extends TestCase
{
    private const LOCALE_FR_CH = 'fr_CH';

    private const PERCENT_SIGN = '%';

    private const TIME_ZONE = 'Europe/Zurich';

    public function getDateFormatterPatterns(): array
    {
        return [
            ['dd/mm/yy', 'dd/mm/yyyy'],
            ['dd-mm-yy', 'dd-mm-yyyy'],
            ['d/m/yy', 'd/m/yyyy'],
        ];
    }

    public function getFormatAmounts(): array
    {
        return [
            [0, '0.00'],
            [0.0, '0.00'],
            ['0', '0.00'],
            ['0.0', '0.00'],

            [1000, "1'000.00"],
            [1000.0, "1'000.00"],
            ['1000', "1'000.00"],
            ['1000.0', "1'000.00"],

            [-1000, "-1'000.00"],

            [-0, '0.00'],
            // [-0.0, "-0.00"],

            [0.14, '0.14'],
            [0.15, '0.15'],
            [0.16, '0.16'],

            [0.114, '0.11'],
            [0.115, '0.12'],
            [0.116, '0.12'],
        ];
    }

    public function getFormatDates(): array
    {
        return [
            [$this->createDate('2022-02-20'), '20.02.2022'],
            [$this->createDate('2022-02-20'), '20.02.2022', \IntlDateFormatter::SHORT],
            [$this->createDate('2022-02-20'), '20 févr. 2022', \IntlDateFormatter::MEDIUM],
            [$this->createDate('2022-02-20'), '20 février 2022', \IntlDateFormatter::LONG],
        ];
    }

    public function getFormatDateTimes(): array
    {
        return [
            [$this->createDate('2022-02-20 12:59:59'), '20.02.2022 12:59'],
            [$this->createDate('2022-02-20 12:59:59'), '20.02.2022 12:59', \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT],
            [$this->createDate('2022-02-20 12:59:59'), '20 févr. 2022 à 12:59', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT],
            [$this->createDate('2022-02-20 12:59:59'), '20 février 2022 à 12:59', \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT],

            [$this->createDate('2022-02-20 12:59:59'), '20.02.2022 12:59:59', \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM],
            [$this->createDate('2022-02-20 12:59:59'), '20.02.2022 12:59:59 UTC+1', \IntlDateFormatter::SHORT, \IntlDateFormatter::LONG],

            [$this->createDate('2022-02-20 12:59:59'), '20 févr. 2022 à 12:59:59', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM],
            [$this->createDate('2022-02-20 12:59:59'), '20 févr. 2022 à 12:59:59 UTC+1', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::LONG],

            [$this->createDate('2022-02-20 12:59:59'), '20 février 2022 à 12:59:59', \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM],
            [$this->createDate('2022-02-20 12:59:59'), '20 février 2022 à 12:59:59 UTC+1', \IntlDateFormatter::LONG, \IntlDateFormatter::LONG],
        ];
    }

    public function getFormatIds(): array
    {
        return [
            [0, '000000'],
            [0.0, '000000'],
            ['0', '000000'],
            ['0.0', '000000'],
            [1, '000001'],
            [1.0, '000001'],
        ];
    }

    public function getFormatInts(): array
    {
        return [
            [0, '0'],
            [0.0, '0'],
            ['0', '0'],
            ['0.0', '0'],
            [1, '1'],
            [1.0, '1'],
            [-1, '-1'],
            [-1.0, '-1'],
        ];
    }

    public function getFormatPercents(): array
    {
        return [
            [0, '0%'],
            [0, '0', false],

            [0, '0.0%', true, 1],
            [0, '0.00%', true, 2],

            [0, '0.0', false, 1],
            [0, '0.00', false, 2],

            [0.1, '10%'],
            [0.15, '15%'],
        ];
    }

    public function getFormatTimes(): array
    {
        return [
            [$this->createDate('12:59:59'), '12:59'],
            [$this->createDate('12:59:59'), '12:59', \IntlDateFormatter::SHORT],
            [$this->createDate('12:59:59'), '12:59:59', \IntlDateFormatter::MEDIUM],
            [$this->createDate('12:59:59'), '12:59:59 UTC+1', \IntlDateFormatter::LONG],
        ];
    }

    /**
     *  @param mixed $pattern
     *  @dataProvider getDateFormatterPatterns
     */
    public function testDateFormatterPattern($pattern, string $expected, ?int $datetype = null, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::getDateFormatter($datetype, $timetype, self::TIME_ZONE, \IntlDateFormatter::GREGORIAN, $pattern);
        $this->assertEquals($expected, $actual->getPattern());
    }

    public function testDateType(): void
    {
        $this->assertEquals(\IntlDateFormatter::SHORT, FormatUtils::getDateType());
    }

    public function testDecimal(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $this->assertEquals('.', FormatUtils::getDecimal());
    }

    /**
     *  @param mixed $number
     *  @dataProvider getFormatAmounts
     */
    public function testFormatAmount($number, string $expected): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        $actual = FormatUtils::formatAmount($number);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $date
     *  @dataProvider getFormatDates
     */
    public function testFormatDate($date, string $expected, ?int $datetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDate($date, $datetype, self::TIME_ZONE);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $date
     *  @dataProvider getFormatDateTimes
     */
    public function testFormatDateTime($date, string $expected, ?int $datetype = null, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDateTime($date, $datetype, $timetype, self::TIME_ZONE);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $number
     *  @dataProvider getFormatIds
     */
    public function testFormatId($number, string $expected): void
    {
        $actual = FormatUtils::formatId($number);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $number
     *  @dataProvider getFormatInts
     */
    public function testFormatInt($number, string $expected): void
    {
        $actual = FormatUtils::formatInt($number);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $number
     *  @dataProvider getFormatPercents
     */
    public function testFormatPercent($number, string $expected, bool $includeSign = true, int $decimals = 0, int $roundingMode = \NumberFormatter::ROUND_DOWN): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        $actual = FormatUtils::formatPercent($number, $includeSign, $decimals, $roundingMode);
        $this->assertEquals($expected, $actual);

        $contains = str_contains($actual, self::PERCENT_SIGN);
        $this->assertEquals($includeSign, $contains);

        $ends_with = str_ends_with($actual, self::PERCENT_SIGN);
        $this->assertEquals($ends_with, $contains);
    }

    /**
     *  @param mixed $date
     *  @dataProvider getFormatTimes
     */
    public function testFormatTime($date, string $expected, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatTime($date, $timetype, self::TIME_ZONE);
        $this->assertEquals($expected, $actual);
    }

    public function testGrouping(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $this->assertEquals("'", FormatUtils::getGrouping());
    }

    public function testPercent(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $this->assertEquals(self::PERCENT_SIGN, FormatUtils::getPercent());
    }

    public function testTimeType(): void
    {
        $this->assertEquals(\IntlDateFormatter::SHORT, FormatUtils::getTimeType());
    }

    private function createDate(string $time): \DateTime
    {
        return new \DateTime($time, new \DateTimeZone(self::TIME_ZONE));
    }
}
