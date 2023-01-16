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

use App\Util\FormatUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the {@link FormatUtils} class.
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
            [-0.0, '0.00'],
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
        $date = $this->createDate();

        return [
            [$date, '20.02.2022'],
            [$date, '20.02.2022', \IntlDateFormatter::SHORT],
            [$date, '20 févr. 2022', \IntlDateFormatter::MEDIUM],
            [$date, '20 février 2022', \IntlDateFormatter::LONG],
            [$date, 'dimanche, 20 février 2022', \IntlDateFormatter::FULL],

            [null, null],
            [self::TIME_STAMP, '20.02.2022'],
        ];
    }

    public function getDateTimes(): \Generator
    {
        $date = $this->createDate();

        yield [$date, '20.02.2022 12:59'];

        yield [$date, '12:59', \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT];

        yield [$date, '20.02.2022', \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE];
        yield [$date, '20.02.2022 12:59', \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT];
        yield [$date, '20.02.2022 12:59:59', \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM];
        yield [$date, '20.02.2022 12:59:59 UTC+1', \IntlDateFormatter::SHORT, \IntlDateFormatter::LONG];

        yield [$date, '20 févr. 2022, 12:59', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT];
        yield [$date, '20 févr. 2022, 12:59:59', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM];
        yield [$date, '20 févr. 2022, 12:59:59 UTC+1', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::LONG];

        yield [$date, '20 février 2022 à 12:59', \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT];
        yield [$date, '20 février 2022 à 12:59:59', \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM];
        yield [$date, '20 février 2022 à 12:59:59 UTC+1', \IntlDateFormatter::LONG, \IntlDateFormatter::LONG];

        yield [$date, 'dimanche, 20 février 2022', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE];
        yield [$date, 'dimanche, 20 février 2022 à 12:59', \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT];
        yield [$date, 'dimanche, 20 février 2022 à 12:59:59', \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM];
        yield [$date, 'dimanche, 20 février 2022 à 12:59:59 UTC+1', \IntlDateFormatter::FULL, \IntlDateFormatter::LONG];
        yield [$date, 'dimanche, 20 février 2022 à 12.59:59 h heure normale d’Europe centrale', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL];

        yield [null, null];
        yield [self::TIME_STAMP, '20.02.2022 12:59'];
    }

    public function getIds(): array
    {
        return [
            [0, '000000'],
            [0.0, '000000'],
            [-0.0, '000000'],
            ['0', '000000'],
            ['0.0', '000000'],
            [1, '000001'],
            [1.0, '000001'],
            [-0, '000000'],
            [null, '000000'],
            [123456, '123456'],
            [-123456, '-123456'],
        ];
    }

    public function getIntegers(): array
    {
        return [
            [0, '0'],
            [0.0, '0'],
            [-0.0, '0'],
            ['0', '0'],
            ['0.0', '0'],
            [1, '1'],
            [1.0, '1'],
            [-1, '-1'],
            [-1.0, '-1'],
            [null, '0'],
            [1000, "1'000"],
            [-1000, "-1'000"],
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
        $date = $this->createDate();

        return [
            [$date, '12:59'],
            [$date, '12:59', \IntlDateFormatter::SHORT],
            [$date, '12:59:59', \IntlDateFormatter::MEDIUM],
            [$date, '12:59:59 UTC+1', \IntlDateFormatter::LONG],
            [$date, '12.59:59 h heure normale d’Europe centrale', \IntlDateFormatter::FULL],

            [null, null],
            [self::TIME_STAMP, '12:59'],
        ];
    }

    /**
     *  @dataProvider getDateFormatterPatterns
     */
    public function testDateFormatterPattern(mixed $pattern, string $expected, ?int $datetype = null, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::getDateFormatter($datetype, $timetype, self::TIME_ZONE, \IntlDateFormatter::GREGORIAN, $pattern);
        self::assertEquals($expected, $actual->getPattern());
    }

    public function testDateType(): void
    {
        self::assertEquals(\IntlDateFormatter::SHORT, FormatUtils::getDateType());
    }

    public function testDecimal(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        self::assertEquals('.', FormatUtils::getDecimal());
    }

    /**
     *  @dataProvider getAmounts
     */
    public function testFormatAmount(mixed $number, string $expected): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        $actual = FormatUtils::formatAmount($number);
        self::assertEquals($expected, $actual);
    }

    /**
     *  @dataProvider getDates
     */
    public function testFormatDate(mixed $date, mixed $expected, ?int $datetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDate($date, $datetype, self::TIME_ZONE);
        self::assertEquals($expected, $actual);
    }

    /**
     *  @dataProvider getDateTimes
     */
    public function testFormatDateTime(mixed $date, mixed $expected, ?int $datetype = null, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDateTime($date, $datetype, $timetype, self::TIME_ZONE);
        self::assertEquals($expected, $actual);
    }

    /**
     *  @dataProvider getIds
     */
    public function testFormatId(mixed $number, string $expected): void
    {
        $actual = FormatUtils::formatId($number);
        self::assertEquals($expected, $actual);
    }

    /**
     *  @dataProvider getIntegers
     */
    public function testFormatInteger(mixed $number, string $expected): void
    {
        $actual = FormatUtils::formatInt($number);
        self::assertEquals($expected, $actual);
    }

    /**
     *  @dataProvider getPercents
     */
    public function testFormatPercent(mixed $number, string $expected, bool $includeSign = true, int $decimals = 0, int $roundingMode = \NumberFormatter::ROUND_DOWN): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatPercent($number, $includeSign, $decimals, $roundingMode);
        self::assertEquals($expected, $actual);

        $contains = \str_contains($actual, self::PERCENT_SIGN);
        self::assertEquals($includeSign, $contains);

        $ends_with = \str_ends_with($actual, self::PERCENT_SIGN);
        self::assertEquals($ends_with, $contains);
    }

    /**
     *  @dataProvider getTimes
     */
    public function testFormatTime(mixed $date, mixed $expected, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatTime($date, $timetype, self::TIME_ZONE);
        self::assertEquals($expected, $actual);
    }

    public function testGrouping(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        self::assertEquals("'", FormatUtils::getGrouping());
    }

    public function testPercent(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        self::assertEquals(self::PERCENT_SIGN, FormatUtils::getPercent());
    }

    public function testTimeType(): void
    {
        self::assertEquals(\IntlDateFormatter::SHORT, FormatUtils::getTimeType());
    }

    private function createDate(): \DateTime
    {
        return new \DateTime(self::DATE_TIME, new \DateTimeZone(self::TIME_ZONE));
    }
}
