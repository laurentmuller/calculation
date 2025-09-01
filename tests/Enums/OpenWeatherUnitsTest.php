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

namespace App\Tests\Enums;

use App\Enums\OpenWeatherUnits;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OpenWeatherUnitsTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @phpstan-return \Generator<int, array{OpenWeatherUnits, array<string, string>}>
     */
    public static function getAttributes(): \Generator
    {
        yield [
            OpenWeatherUnits::IMPERIAL,
            [
                'system' => 'imperial',
                'speed' => 'mph',
                'temperature' => '°F',
                'pressure' => 'hPa',
                'degree' => '°',
                'percent' => '%',
                'volume' => 'mm',
            ],
        ];

        yield [
            OpenWeatherUnits::METRIC,
            [
                'system' => 'metric',
                'speed' => 'm/s',
                'temperature' => '°C',
                'pressure' => 'hPa',
                'degree' => '°',
                'percent' => '%',
                'volume' => 'mm',
            ],
        ];
    }

    /**
     * @phpstan-return \Generator<int, array{string, OpenWeatherUnits}>
     */
    public static function getDegrees(): \Generator
    {
        yield ['°F', OpenWeatherUnits::IMPERIAL];
        yield ['°C', OpenWeatherUnits::METRIC];
    }

    /**
     * @phpstan-return \Generator<int, array{string, OpenWeatherUnits}>
     */
    public static function getLabels(): \Generator
    {
        yield ['openweather.current.imperial.text', OpenWeatherUnits::IMPERIAL];
        yield ['openweather.current.metric.text', OpenWeatherUnits::METRIC];
    }

    /**
     * @phpstan-return \Generator<int, array{string, OpenWeatherUnits}>
     */
    public static function getSpeeds(): \Generator
    {
        yield ['mph', OpenWeatherUnits::IMPERIAL];
        yield ['m/s', OpenWeatherUnits::METRIC];
    }

    /**
     * @phpstan-return \Generator<int, array{string, OpenWeatherUnits}>
     */
    public static function getValues(): \Generator
    {
        yield ['imperial', OpenWeatherUnits::IMPERIAL];
        yield ['metric', OpenWeatherUnits::METRIC];
    }

    #[DataProvider('getAttributes')]
    public function testAttributes(OpenWeatherUnits $units, array $expected): void
    {
        $actual = $units->attributes();
        self::assertSame($expected, $actual);
    }

    public function testCount(): void
    {
        self::assertCount(2, OpenWeatherUnits::cases());
        self::assertCount(2, OpenWeatherUnits::sorted());
    }

    public function testDefault(): void
    {
        $expected = OpenWeatherUnits::METRIC;
        $actual = OpenWeatherUnits::getDefault();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getDegrees')]
    public function testDegree(string $expected, OpenWeatherUnits $unit): void
    {
        $actual = $unit->getDegree();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testLabel(string $expected, OpenWeatherUnits $unit): void
    {
        $actual = $unit->getReadable();
        self::assertSame($expected, $actual);
    }

    public function testSorted(): void
    {
        $expected = [
            OpenWeatherUnits::METRIC,
            OpenWeatherUnits::IMPERIAL,
        ];
        $actual = OpenWeatherUnits::sorted();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getSpeeds')]
    public function testSpeed(string $expected, OpenWeatherUnits $unit): void
    {
        $actual = $unit->getSpeed();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testTranslate(string $expected, OpenWeatherUnits $unit): void
    {
        $translator = $this->createMockTranslator();
        $actual = $unit->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(string $expected, OpenWeatherUnits $unit): void
    {
        $actual = $unit->value;
        self::assertSame($expected, $actual);
    }
}
