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

    /**
     * @return array<array{0: string|int|float, 1: string}>
     */
    public static function getAmounts(): array
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

    /**
     * @return array<array{0: string, 1: string}>
     */
    public static function getDateFormatterPatterns(): array
    {
        return [
            ['dd/mm/yy', 'dd/mm/yyyy'],
            ['dd-mm-yy', 'dd-mm-yyyy'],
            ['d/m/yy', 'd/m/yyyy'],
        ];
    }

    /**
     * @return array<array{0: \DateTimeInterface|int|null, 1: string|null, 2?: int}>
     *
     * @throws \Exception
     */
    public static function getDates(): array
    {
        $date = self::createDate();

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

    /**
     * @psalm-return \Generator<int, array{0: \DateTimeInterface|int|null, 1: string|null, 2?: (-1|0|1|2|3), 3?: (-1|0|1|2|3)}, mixed, void>
     *
     * @throws \Exception
     */
    public static function getDateTimes(): \Generator
    {
        $date = self::createDate();

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

    /**
     * @return array<array{0: int|float|string|null, 1: string}>
     */
    public static function getIds(): array
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

    /**
     * @return array<array{0: int|float|string|null, 1: string}>
     */
    public static function getIntegers(): array
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

    /**
     * @return array<array{0: int|float|string|null, 1: string, 2?: bool, 3?: int, 4?: int}>
     */
    public static function getPercents(): array
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

    /**
     * @return array<array{0: \DateTimeInterface|int|null, 1: string|null, 2?: int}>
     *
     * @throws \Exception
     */
    public static function getTimes(): array
    {
        $date = self::createDate();

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

    #[\PHPUnit\Framework\Attributes\DataProvider('getDateFormatterPatterns')]
    public function testDateFormatterPattern(string $pattern, string $expected, ?int $datetype = null, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::getDateFormatter($datetype, $timetype, self::TIME_ZONE, \IntlDateFormatter::GREGORIAN, $pattern);
        self::assertSame($expected, $actual->getPattern());
    }

    public function testDateType(): void
    {
        self::assertSame(\IntlDateFormatter::SHORT, FormatUtils::getDateType());
    }

    public function testDecimal(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        self::assertSame('.', FormatUtils::getDecimal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getAmounts')]
    public function testFormatAmount(string|int|float $number, string $expected): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        $actual = FormatUtils::formatAmount($number);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDates')]
    public function testFormatDate(\DateTimeInterface|int|null $date, string|null $expected, ?int $datetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDate($date, $datetype, self::TIME_ZONE);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDateTimes')]
    public function testFormatDateTime(\DateTimeInterface|int|null $date, string|null $expected, ?int $datetype = null, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatDateTime($date, $datetype, $timetype, self::TIME_ZONE);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIds')]
    public function testFormatId(int|float|string|null $number, string $expected): void
    {
        $actual = FormatUtils::formatId($number);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIntegers')]
    public function testFormatInteger(int|float|string|null $number, string $expected): void
    {
        $actual = FormatUtils::formatInt($number);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPercents')]
    public function testFormatPercent(int|float|string|null $number, string $expected, bool $includeSign = true, int $decimals = 0, int $roundingMode = \NumberFormatter::ROUND_DOWN): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatPercent($number, $includeSign, $decimals, $roundingMode);
        self::assertSame($expected, $actual);

        $contains = \str_contains($actual, self::PERCENT_SIGN);
        self::assertSame($includeSign, $contains);

        $ends_with = \str_ends_with($actual, self::PERCENT_SIGN);
        self::assertSame($ends_with, $contains);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTimes')]
    public function testFormatTime(\DateTimeInterface|int|null $date, string|null $expected, ?int $timetype = null): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_TIME, self::LOCALE_FR_CH);
        $actual = FormatUtils::formatTime($date, $timetype, self::TIME_ZONE);
        self::assertSame($expected, $actual);
    }

    public function testGrouping(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        self::assertSame("'", FormatUtils::getGrouping());
    }

    public function testPercent(): void
    {
        \Locale::setDefault(self::LOCALE_FR_CH);
        \setlocale(\LC_NUMERIC, self::LOCALE_FR_CH);
        self::assertSame(self::PERCENT_SIGN, FormatUtils::getPercent());
    }

    public function testTimeType(): void
    {
        self::assertSame(\IntlDateFormatter::SHORT, FormatUtils::getTimeType());
    }

    /**
     * @throws \Exception
     */
    private static function createDate(): \DateTimeInterface
    {
        return new \DateTime(self::DATE_TIME, new \DateTimeZone(self::TIME_ZONE));
    }
}
