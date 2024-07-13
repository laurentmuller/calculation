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

namespace App\Tests\Service;

use App\Service\PositionService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PositionServiceTest extends TestCase
{
    use TranslatorMockTrait;

    public static function getDirections(): \Iterator
    {
        yield [0, 'N'];
        yield [22, 'N / N-E'];
        yield [45, 'N-E'];
        yield [68, 'E / N-E'];
        yield [90, 'E'];
        yield [113, 'E / S-E'];
        yield [135, 'S-E'];
        yield [158, 'S / S-E'];
        yield [180, 'S'];
        yield [203, 'S / S-W'];
        yield [225, 'S-W'];
        yield [248, 'W / S-W'];
        yield [270, 'W'];
        yield [296, 'W / N-W'];
        yield [315, 'N-W'];
        yield [338, 'N / N-W'];
        yield [360, 'N'];
        yield [361, 'N'];
    }

    public static function getFormatDirections(): \Iterator
    {
        yield [0, 'openweather.direction.N'];
        yield [90, 'openweather.direction.E'];
        yield [180, 'openweather.direction.S'];
        yield [270, 'openweather.direction.W'];
    }

    public static function getLatitudes(): \Iterator
    {
        yield [-91.0, '', true];
        yield [-90.0, '90° 0\' 0" openweather.direction.S'];
        yield [0.0, '0° 0\' 0" openweather.direction.N'];
        yield [90.0, '90° 0\' 0" openweather.direction.N'];
        yield [91.0, '', true];
    }

    public static function getLongitudes(): \Iterator
    {
        yield [-181.0, '', true];
        yield [-180.0, '180° 0\' 0" openweather.direction.W'];
        yield [0.0, '0° 0\' 0" openweather.direction.E'];
        yield [180.0, '180° 0\' 0" openweather.direction.E'];
        yield [181.0, '', true];
    }

    public static function getPositions(): \Iterator
    {
        yield [-90.0, -180.0, '90° 0\' 0" openweather.direction.S, 180° 0\' 0" openweather.direction.W'];
        yield [0.0, 0.0, '0° 0\' 0" openweather.direction.N, 0° 0\' 0" openweather.direction.E'];
        yield [90.0, +180, '90° 0\' 0" openweather.direction.N, 180° 0\' 0" openweather.direction.E'];
        yield [-91.0, 0, '', true];
        yield [+91.0, 0, '', true];
        yield [0, -181, '', true];
        yield [0, +181, '', true];
    }

    #[DataProvider('getFormatDirections')]
    public function testFormatDirection(int $deg, string $expected): void
    {
        $service = $this->createService();
        $actual = $service->formatDirection($deg);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLatitudes')]
    public function testFormatLatitude(float $lat, string $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $service = $this->createService();
        $actual = $service->formatLatitude($lat);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLongitudes')]
    public function testFormatLongitude(float $lng, string $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $service = $this->createService();
        $actual = $service->formatLongitude($lng);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getPositions')]
    public function testFormatPosition(float $lat, float $lng, string $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $service = $this->createService();
        $actual = $service->formatPosition($lat, $lng);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getDirections')]
    public function testGetDirection(float $deg, string $expected): void
    {
        $service = $this->createService();
        $actual = $service->getDirection($deg);
        self::assertSame($expected, $actual);
    }

    public function testGetGoogleMapUrl(): void
    {
        $service = $this->createService();
        $actual = $service->getGoogleMapUrl(0.1, 0.1);
        self::assertStringStartsWith('https://www.google.ch/maps/place/0.1,0.1', $actual);
    }

    private function createService(): PositionService
    {
        $translator = $this->createMockTranslator();

        return new PositionService($translator);
    }
}
