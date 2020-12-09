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

namespace App\Tests\Service;

use App\Service\OpenWeatherService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test case for OpenWeatherService.
 *
 * @author Laurent Muller
 */
class OpenWeatherServiceTest extends KernelTestCase
{
    public const CITY_INVALID = 0;

    public const CITY_VALID = 2660718;

    /*
     * the debug mode
     */
    private $debug = false;

    /**
     * @var OpenWeatherService
     */
    private $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(OpenWeatherService::class);
        $this->debug = self::$kernel->isDebug();
    }

    public function testCurrent(): void
    {
        $this->assertNotNull($this->service);
        $result = $this->service->current(self::CITY_VALID);
        $this->assertIsArray($result);
        $this->assertEquals(self::CITY_VALID, $result['id']);

        $this->assertIsArray($result['units']);
        $this->validateResult($result, true);
    }

    public function testCurrentInvalid(): void
    {
        $this->assertNotNull($this->service);
        $result = $this->service->current(self::CITY_INVALID);
        $this->assertFalse($result);
    }

    public function testDaily(): void
    {
        $count = 5;
        $result = $this->service->daily(self::CITY_VALID, $count);
        $this->assertIsArray($result);
        $this->assertEquals($count, $result['cnt']);
        $this->assertCount($count, $result['list']);
        $this->assertIsArray($result['units']);

        $this->assertIsArray($result['city']);
        $city = $result['city'];
        $this->assertIsArray($city['coord']);
        $this->validateCoord($city['coord']);

        $result = $result['list'][0];
        $this->assertIsInt($result['sunrise']);
        $this->assertIsInt($result['sunset']);
    }

    public function testDailyInvalid(): void
    {
        $result = $this->service->daily(self::CITY_INVALID);
        $this->assertFalse($result);
    }

    public function testGroup(): void
    {
        $cityIds = [self::CITY_VALID];
        $result = $this->service->group($cityIds);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['cnt']);
        $this->assertCount(1, $result['list']);
        $this->assertIsArray($result['units']);

        $result = $result['list'][0];
        $this->assertIsArray($result);
        $this->validateResult($result, false);
    }

    public function testGroupInvalidCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->group(\range(0, 25));
    }

    private function doEcho(array $result): void
    {
        if ($this->debug) {
            echo \json_encode($result, JSON_PRETTY_PRINT);
        }
    }

    private function validateCoord(array $data): void
    {
        $this->assertIsNumeric($data['lon']);
        $this->assertIsNumeric($data['lat']);
    }

    private function validateMain(array $data): void
    {
        $this->assertIsNumeric($data['temp']);
        $this->assertIsNumeric($data['feels_like']);
        $this->assertIsNumeric($data['temp_min']);
        $this->assertIsNumeric($data['temp_max']);
        $this->assertIsNumeric($data['pressure']);
        $this->assertIsNumeric($data['humidity']);
    }

    private function validateResult(array $result, bool $includeDeg): void
    {
        $this->assertIsArray($result['coord']);
        $this->validateCoord($result['coord']);

        $this->assertIsArray($result['weather']);
        $this->validateWeather($result['weather']);

        $this->assertIsArray($result['main']);
        $this->validateMain($result['main']);

        $this->assertIsArray($result['wind']);
        $this->validateWind($result['wind'], $includeDeg);

        $this->assertIsArray($result['sys']);
        $this->validateSys($result['sys']);
    }

    private function validateSys(array $data): void
    {
        $this->assertIsString($data['country']);
        $this->assertEquals(2, \strlen($data['country']));
        $this->assertIsInt($data['sunrise']);
        $this->assertIsInt($data['sunset']);
    }

    private function validateWeather(array $data): void
    {
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['main']);
        $this->assertIsString($data['description']);
        $this->assertIsString($data['icon']);
        $this->assertIsString($data['icon_big']);
        $this->assertIsString($data['icon_small']);
    }

    private function validateWind(array $data, bool $includeDeg): void
    {
        $this->assertIsNumeric($data['speed']);
        if ($includeDeg) {
            $this->assertIsNumeric($data['deg']);
        }
    }
}
