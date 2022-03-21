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
    private const DATE_TIME = '2022-02-20 12:59:59';

    private const LOCALE_FR_CH = 'fr_CH';

    private const PERCENT_SIGN = '%';

    private const TIME_STAMP = 1645358399;

    private const TIME_ZONE = 'Europe/Zurich';

    public function getAmounts(): array
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
            [-0.0, '0.00'],

            [0.14, '0.14'],
            [0.15, '0.15'],
            [0.16, '0.16'],

            [0.114, '0.11'],
            [0.115, '0.12'],
            [0.116, '0.12'],
        ];
    }

    public function getDateFormatterPatterns(): array
    {
        return [
            ['dd/mm/yy', 'dd/mm/yyyy'],
            ['dd-mm-yy', 'dd-mm-yyyy'],
            ['d/m/yy', 'd/m/yyyy'],
        ];
    }

    public function getDates(): array
    {
        return [
            [$this->createDate(), '20.02.2022'],
            [$this->createDate(), '20.02.2022', \IntlDateFormatter::SHORT],
            [$this->createDate(), '20 févr. 2022', \IntlDateFormatter::MEDIUM],
            [$this->createDate(), '20 février 2022', \IntlDateFormatter::LONG],
            [$this->createDate(), 'dimanche, 20 février 2022', \IntlDateFormatter::FULL],

            [null, null],
            [self::TIME_STAMP, '20.02.2022'],
        ];
    }

    public function getDateTimes(): array
    {
        return [
            [$this->createDate(), '20.02.2022 12:59'],
            [$this->createDate(), '20.02.2022 12:59', \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT],
            [$this->createDate(), '20 févr. 2022, 12:59', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT],
            [$this->createDate(), '20 février 2022 à 12:59', \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT],

            [$this->createDate(), '20.02.2022 12:59:59', \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM],
            [$this->createDate(), '20.02.2022 12:59:59 UTC+1', \IntlDateFormatter::SHORT, \IntlDateFormatter::LONG],

            [$this->createDate(), '20 févr. 2022, 12:59:59', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM],
            [$this->createDate(), '20 févr. 2022, 12:59:59 UTC+1', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::LONG],

            [$this->createDate(), '20 février 2022 à 12:59:59', \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM],
            [$this->createDate(), '20 février 2022 à 12:59:59 UTC+1', \IntlDateFormatter::LONG, \IntlDateFormatter::LONG],

            [$this->createDate(), '20.02.2022', \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE],
            [$this->createDate(), '12:59', \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT],

            [$this->createDate(), 'dimanche, 20 février 2022', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE],
            [$this->createDate(), 'dimanche, 20 février 2022 à 12:59', \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT],
            [$this->createDate(), 'dimanche, 20 février 2022 à 12:59:59', \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM],
            [$this->createDate(), 'dimanche, 20 février 2022 à 12:59:59 UTC+1', \IntlDateFormatter::FULL, \IntlDateFormatter::LONG],
            [$this->createDate(), 'dimanche, 20 février 2022 à 12.59:59 h heure normale d’Europe centrale', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL],

            [null, null],
            [self::TIME_STAMP, '20.02.2022 12:59'],
        ];
    }

    public function getIds(): array
    {
        return [
            [0, '000000'],
            [0.0, '000000'],
            ['0', '000000'],
            ['0.0', '000000'],
            [1, '000001'],
            [1.0, '000001'],
            [-0, '000000'],
            [null, '000000'],
        ];
    }

    public function getInts(): array
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
            [null, '0'],
        ];
    }

    public function getPercents(): array
    {
        return [
            [0, '0%'],
            [-0, '0%'],
            [0, '0', false],

            [0, '0.0%', true, 1],
            [0, '0.00%', true, 2],

            [0, '0.0', false, 1],
            [0, '0.00', false, 2],

            [0.1, '10%'],
            [0.15, '15%'],

            [null, '0%'],
            [null, '0', false],
            [null, '0.0%', true, 1],
            [null, '0.00%', true, 2],
        ];
    }

    public function getTimes(): array
    {
        return [
            [$this->createDate(), '12:59'],
            [$this->createDate(), '12:59', \IntlDateFormatter::SHORT],
            [$this->createDate(), '12:59:59', \IntlDateFormatter::MEDIUM],
            [$this->createDate(), '12:59:59 UTC+1', \IntlDateFormatter::LONG],
            [$this->createDate(), '12.59:59 h heure normale d’Europe centrale', \IntlDateFormatter::FULL],

            [null, null],
            [self::TIME_STAMP, '12:59'],
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
     *  @dataProvider getAmounts
     */
    public function testFormatAmount($number, string $expected): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        $actual = FormatUtils::formatAmount($number);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $date
     *  @param mixed $expected
     *  @dataProvider getDates
     */
    public function testFormatDate($date, $expected, ?int $datetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDate($date, $datetype, self::TIME_ZONE);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $date
     *  @param mixed $expected
     *  @dataProvider getDateTimes
     */
    public function testFormatDateTime($date, $expected, ?int $datetype = null, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDateTime($date, $datetype, $timetype, self::TIME_ZONE);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $number
     *  @dataProvider getIds
     */
    public function testFormatId($number, string $expected): void
    {
        $actual = FormatUtils::formatId($number);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $number
     *  @dataProvider getInts
     */
    public function testFormatInt($number, string $expected): void
    {
        $actual = FormatUtils::formatInt($number);
        $this->assertEquals($expected, $actual);
    }

    /**
     *  @param mixed $number
     *  @dataProvider getPercents
     */
    public function testFormatPercent($number, string $expected, bool $includeSign = true, int $decimals = 0, int $roundingMode = \NumberFormatter::ROUND_DOWN): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        $actual = FormatUtils::formatPercent($number, $includeSign, $decimals, $roundingMode);
        $this->assertEquals($expected, $actual);

        $contains = \str_contains($actual, self::PERCENT_SIGN);
        $this->assertEquals($includeSign, $contains);

        $ends_with = \str_ends_with($actual, self::PERCENT_SIGN);
        $this->assertEquals($ends_with, $contains);
    }

    /**
     *  @param mixed $date
     *  @param mixed $expected
     *  @dataProvider getTimes
     */
    public function testFormatTime($date, $expected, ?int $timetype = null): void
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

    private function createDate(string $time = self::DATE_TIME, string $timezone = self::TIME_ZONE): \DateTime
    {
        return new \DateTime($time, new \DateTimeZone($timezone));
    }
}
