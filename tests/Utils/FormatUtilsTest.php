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

use App\Model\LogFile;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(FormatUtils::class)]
class FormatUtilsTest extends TestCase
{
    private const DATE_TIME = '2022-02-20 12:59:59';

    private const PERCENT_SYMBOL = '%';

    private const TIME_STAMP = 1_645_358_399;

    private const TIME_ZONE = 'Europe/Zurich';

    protected function setUp(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        \setlocale(\LC_TIME, FormatUtils::DEFAULT_LOCALE);
    }

    public static function getAmounts(): \Iterator
    {
        yield [null, '0.00'];
        yield ['', '0.00'];
        yield ['fake', '0.00'];

        yield [0, '0.00'];
        yield [0.0, '0.00'];
        yield [-0, '0.00'];
        yield [-0.0, '0.00'];

        yield ['0', '0.00'];
        yield ['0.0', '0.00'];
        yield ['-0', '0.00'];
        yield ['-0.0', '0.00'];

        yield [1000, "1'000.00"];
        yield [1000.0, "1'000.00"];
        yield [-1000, "-1'000.00"];
        yield [-1000.0, "-1'000.00"];

        yield ['1000', "1'000.00"];
        yield ['1000.0', "1'000.00"];
        yield ['-1000', "-1'000.00"];
        yield ['-1000.0', "-1'000.00"];

        yield [0.14, '0.14'];
        yield [0.15, '0.15'];
        yield [0.16, '0.16'];
        yield [0.114, '0.11'];
        yield [0.115, '0.12'];
        yield [0.116, '0.12'];
    }

    public static function getDateFormatterPatterns(): \Iterator
    {
        yield ['dd/mm/yy', 'dd/mm/yyyy'];
        yield ['dd-mm-yy', 'dd-mm-yyyy'];
        yield ['d/m/yy', 'd/m/yyyy'];
    }

    /**
     * @throws \Exception
     */
    public static function getDates(): \Iterator
    {
        $date = self::createDate();
        yield [$date, '20.02.2022'];
        yield [$date, '20.02.2022', \IntlDateFormatter::SHORT];
        yield [$date, '20 févr. 2022', \IntlDateFormatter::MEDIUM];
        yield [$date, '20 février 2022', \IntlDateFormatter::LONG];
        yield [$date, 'dimanche, 20 février 2022', \IntlDateFormatter::FULL];
        yield [null, null];
        yield [self::TIME_STAMP, '20.02.2022'];

        yield [self::TIME_STAMP, 'Février', null, 'MMMM'];
    }

    /**
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
        yield [
            $date,
            'dimanche, 20 février 2022 à 12:59:59 UTC+1',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::LONG,
        ];
        yield [
            $date,
            'dimanche, 20 février 2022 à 12.59:59 h heure normale d’Europe centrale',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
        ];

        yield [null, null];
        yield [self::TIME_STAMP, '20.02.2022 12:59'];
    }

    public static function getIds(): \Iterator
    {
        yield [null, '000000'];
        yield ['', '000000'];
        yield ['fake', '000000'];

        yield [0, '000000'];
        yield [-0, '000000'];
        yield [0.0, '000000'];
        yield [-0.0, '000000'];

        yield ['0', '000000'];
        yield ['-0', '000000'];
        yield ['0.0', '000000'];
        yield ['-0.0', '000000'];

        yield [1, '000001'];
        yield [1.0, '000001'];

        yield ['1', '000001'];
        yield ['1.0', '000001'];

        yield [123456, '123456'];
        yield [-123456, '-123456'];
    }

    public static function getIntegers(): \Iterator
    {
        yield [null, '0'];
        yield ['', '0'];
        yield ['fake', '0'];

        yield [0, '0'];
        yield [0.0, '0'];
        yield [-0, '0'];
        yield [-0.0, '0'];

        yield ['0', '0'];
        yield ['0.0', '0'];
        yield ['-0', '0'];
        yield ['-0.0', '0'];

        yield [1, '1'];
        yield [1.0, '1'];
        yield [-1, '-1'];
        yield [-1.0, '-1'];
        yield [null, '0'];
        yield [1000, "1'000"];
        yield [-1000, "-1'000"];

        yield [[1, 2, 3], '3'];
        yield [new LogFile(''), '0'];
    }

    public static function getPercents(): \Iterator
    {
        yield [null, '0%'];
        yield [null, '0', false];
        yield [null, '0.0%', true, 1];
        yield [null, '0.00%', true, 2];

        yield ['', '0%'];
        yield ['fake', '0%'];

        yield [0, '0%'];
        yield [-0, '0%'];

        yield ['0', '0%'];
        yield ['-0', '0%'];

        yield [0, '0', false];
        yield [0, '0.0%', true, 1];
        yield [0, '0.00%', true, 2];
        yield [0, '0.0', false, 1];
        yield [0, '0.00', false, 2];
        yield [0.1, '10%'];
        yield [0.15, '15%'];
    }

    /**
     * @throws \Exception
     */
    public static function getTimes(): \Iterator
    {
        $date = self::createDate();
        yield [$date, '12:59'];
        yield [$date, '12:59', \IntlDateFormatter::SHORT];
        yield [$date, '12:59:59', \IntlDateFormatter::MEDIUM];
        yield [$date, '12:59:59 UTC+1', \IntlDateFormatter::LONG];
        yield [$date, '12.59:59 h heure normale d’Europe centrale', \IntlDateFormatter::FULL];
        yield [null, null];
        yield [self::TIME_STAMP, '12:59'];
    }

    /**
     * @psalm-param int<-1,3>|null $dateType
     * @psalm-param int<-1,3>|null $timeType
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDateFormatterPatterns')]
    public function testDateFormatterPattern(
        string $pattern,
        string $expected,
        ?int $dateType = null,
        ?int $timeType = null
    ): void {
        $actual = FormatUtils::getDateFormatter($dateType, $timeType, $pattern, self::TIME_ZONE);
        self::assertSame($expected, $actual->getPattern());
    }

    public function testDateType(): void
    {
        self::assertSame(\IntlDateFormatter::SHORT, FormatUtils::DATE_TYPE);
    }

    public function testDecimalSep(): void
    {
        self::assertSame('.', FormatUtils::DECIMAL_SEP);
    }

    public function testDefaultLocale(): void
    {
        self::assertSame('fr_CH', FormatUtils::DEFAULT_LOCALE);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getAmounts')]
    public function testFormatAmount(string|int|float|null $number, string $expected): void
    {
        $actual = FormatUtils::formatAmount($number);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param int<-1,3>|null $dateType
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDates')]
    public function testFormatDate(
        \DateTimeInterface|int|null $date,
        ?string $expected,
        ?int $dateType = null,
        ?string $pattern = null
    ): void {
        $actual = FormatUtils::formatDate($date, $dateType, $pattern, self::TIME_ZONE);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param int<-1,3>|null $dateType
     * @psalm-param int<-1,3>|null $timeType
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDateTimes')]
    public function testFormatDateTime(
        \DateTimeInterface|int|null $date,
        ?string $expected,
        ?int $dateType = null,
        ?int $timeType = null,
        ?string $pattern = null
    ): void {
        $actual = FormatUtils::formatDateTime($date, $dateType, $timeType, $pattern, self::TIME_ZONE);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIds')]
    public function testFormatId(int|float|string|null $number, string $expected): void
    {
        $actual = FormatUtils::formatId($number);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIntegers')]
    public function testFormatInteger(\Countable|array|int|float|string|null $number, string $expected): void
    {
        $actual = FormatUtils::formatInt($number);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param \NumberFormatter::ROUND_* $roundingMode
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getPercents')]
    public function testFormatPercent(
        int|float|string|null $number,
        string $expected,
        bool $includeSign = true,
        int $decimals = 0,
        int $roundingMode = \NumberFormatter::ROUND_DOWN
    ): void {
        $actual = FormatUtils::formatPercent($number, $includeSign, $decimals, $roundingMode);
        self::assertSame($expected, $actual);

        $contains = \str_contains($actual, self::PERCENT_SYMBOL);
        self::assertSame($includeSign, $contains);

        $ends_with = \str_ends_with($actual, self::PERCENT_SYMBOL);
        self::assertSame($ends_with, $contains);
    }

    /**
     * @psalm-param int<-1,3>|null $timeType
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTimes')]
    public function testFormatTime(
        \DateTimeInterface|int|null $date,
        ?string $expected,
        ?int $timeType = null,
        ?string $pattern = null
    ): void {
        $actual = FormatUtils::formatTime($date, $timeType, $pattern, self::TIME_ZONE);
        self::assertSame($expected, $actual);
    }

    public function testFractionDigits(): void
    {
        self::assertSame(2, FormatUtils::FRACTION_DIGITS);
    }

    public function testPercentSymbol(): void
    {
        self::assertSame(self::PERCENT_SYMBOL, FormatUtils::PERCENT_SYMBOL);
    }

    public function testThousandsSep(): void
    {
        self::assertSame("'", FormatUtils::THOUSANDS_SEP);
    }

    public function testTimeType(): void
    {
        self::assertSame(\IntlDateFormatter::SHORT, FormatUtils::TIME_TYPE);
    }

    /**
     * @throws \Exception
     */
    private static function createDate(): \DateTimeInterface
    {
        return new \DateTime(self::DATE_TIME, new \DateTimeZone(self::TIME_ZONE));
    }
}
