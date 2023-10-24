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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(PositionService::class)]
class PositionServiceTest extends TestCase
{
    public static function getDirections(): array
    {
        return [
            [0, 'N'],
            [22, 'N / N-E'],
            [45, 'N-E'],
            [68, 'E / N-E'],
            [90, 'E'],
            [113, 'E / S-E'],
            [135, 'S-E'],
            [158, 'S / S-E'],
            [180, 'S'],
            [203, 'S / S-W'],
            [225, 'S-W'],
            [248, 'W / S-W'],
            [270, 'W'],
            [296, 'W / N-W'],
            [315, 'N-W'],
            [338, 'N / N-W'],
            [360, 'N'],
            [361, 'N'],
        ];
    }

    public static function getFormatDirections(): array
    {
        return [
            [0, 'openweather.direction.N'],
            [90, 'openweather.direction.E'],
            [180, 'openweather.direction.S'],
            [270, 'openweather.direction.W'],
        ];
    }

    public static function getLatitudes(): array
    {
        return [
            [-91.0, '', true],
            [-90.0, '90° 0\' 0" openweather.direction.S'],
            [0.0, '0° 0\' 0" openweather.direction.N'],
            [90.0, '90° 0\' 0" openweather.direction.N'],
            [91.0, '', true],
        ];
    }

    public static function getLongitudes(): array
    {
        return [
            [-181.0, '', true],
            [-180.0, '180° 0\' 0" openweather.direction.W'],
            [0.0, '0° 0\' 0" openweather.direction.E'],
            [180.0, '180° 0\' 0" openweather.direction.E'],
            [181.0, '', true],
        ];
    }

    public static function getPositions(): array
    {
        return [
            [-90.0, -180.0, '90° 0\' 0" openweather.direction.S, 180° 0\' 0" openweather.direction.W'],
            [0.0, 0.0, '0° 0\' 0" openweather.direction.N, 0° 0\' 0" openweather.direction.E'],
            [90.0, +180, '90° 0\' 0" openweather.direction.N, 180° 0\' 0" openweather.direction.E'],

            [-91.0, 0, '', true],
            [+91.0, 0, '', true],

            [0, -181, '', true],
            [0, +181, '', true],
        ];
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getFormatDirections')]
    public function testFormatDirection(int $deg, string $expected): void
    {
        $service = $this->createService();
        $actual = $service->formatDirection($deg);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLatitudes')]
    public function testFormatLatitude(float $lat, string $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $service = $this->createService();
        $actual = $service->formatLatitude($lat);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLongitudes')]
    public function testFormatLongitude(float $lng, string $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $service = $this->createService();
        $actual = $service->formatLongitude($lng);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getPositions')]
    public function testFormatPosition(float $lat, float $lng, string $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $service = $this->createService();
        $actual = $service->formatPosition($lat, $lng);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDirections')]
    public function testGetDirection(float $deg, string $expected): void
    {
        $service = $this->createService();
        $actual = $service->getDirection($deg);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    private function createService(): PositionService
    {
        $translator = $this->createTranslator();

        return new PositionService($translator);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }
}
