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

use App\Service\OpenWeatherService;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Unit test for {@link OpenWeatherService} class.
 */
class OpenWeatherServiceTest extends KernelTestCase
{
    use ServiceTrait;

    private const CITY_INVALID = 0;

    private const CITY_VALID = 2660718;

    private ?OpenWeatherService $service = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = $this->getService(OpenWeatherService::class);
    }

    public function testCurrent(): void
    {
        self::assertNotNull($this->service);
        $result = $this->service->current(self::CITY_VALID);
        self::assertIsArray($result);
        self::assertEquals(self::CITY_VALID, $result['id']);

        self::assertIsArray($result['units']);
        $this->validateResult($result, true);
    }

    public function testCurrentInvalid(): void
    {
        self::assertNotNull($this->service);
        $result = $this->service->current(self::CITY_INVALID);
        self::assertFalse($result);
    }

    public function testDaily(): void
    {
        $count = 5;
        $result = $this->service->daily(self::CITY_VALID, $count);
        self::assertIsArray($result);
        self::assertEquals($count, $result['cnt']);
        self::assertCount($count, $result['list']);
        self::assertIsArray($result['units']);

        self::assertIsArray($result['city']);
        $city = $result['city'];
        self::assertIsArray($city['coord']);
        $this->validateCoord($city['coord']);

        $result = $result['list'][0];
        self::assertIsInt($result['sunrise']);
        self::assertIsInt($result['sunset']);
    }

    public function testDailyInvalid(): void
    {
        $result = $this->service->daily(self::CITY_INVALID);
        self::assertFalse($result);
    }

    public function testGroup(): void
    {
        $cityIds = [self::CITY_VALID];
        $result = $this->service->group($cityIds);

        self::assertIsArray($result);
        self::assertEquals(1, $result['cnt']);
        self::assertCount(1, $result['list']);
        self::assertIsArray($result['units']);

        // @phpstan-ignore-next-line
        $result = $result['list'][0];
        self::assertIsArray($result);
        $this->validateResult($result, false);
    }

    public function testGroupInvalidCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->group(\range(0, 25));
    }

    private function validateCoord(array $data): void
    {
        self::assertIsNumeric($data['lon']);
        self::assertIsNumeric($data['lat']);
    }

    private function validateMain(array $data): void
    {
        self::assertIsNumeric($data['temp']);
        self::assertIsNumeric($data['feels_like']);
        self::assertIsNumeric($data['temp_min']);
        self::assertIsNumeric($data['temp_max']);
        self::assertIsNumeric($data['pressure']);
        self::assertIsNumeric($data['humidity']);
    }

    private function validateResult(array $result, bool $includeDeg): void
    {
        self::assertIsArray($result['coord']);
        $this->validateCoord($result['coord']);

        self::assertIsArray($result['weather']);
        $this->validateWeather($result['weather']);

        self::assertIsArray($result['main']);
        $this->validateMain($result['main']);

        self::assertIsArray($result['wind']);
        $this->validateWind($result['wind'], $includeDeg);

        self::assertIsArray($result['sys']);
        $this->validateSys($result['sys']);
    }

    private function validateSys(array $data): void
    {
        self::assertIsString($data['country']);
        self::assertEquals(2, \strlen((string) $data['country']));
        self::assertIsInt($data['sunrise']);
        self::assertIsInt($data['sunset']);
    }

    private function validateWeather(array $data): void
    {
        self::assertIsInt($data['id']);
        self::assertIsString($data['main']);
        self::assertIsString($data['description']);
        self::assertIsString($data['icon']);
        self::assertIsString($data['icon_big']);
        self::assertIsString($data['icon_small']);
    }

    private function validateWind(array $data, bool $includeDeg): void
    {
        self::assertIsNumeric($data['speed']);
        if ($includeDeg) {
            self::assertIsNumeric($data['deg']);
        }
    }
}
