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
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(OpenWeatherService::class)]
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

    /**
     * @throws ExceptionInterface
     */
    public function testCurrent(): void
    {
        self::assertNotNull($this->service);
        $result = $this->service->current(self::CITY_VALID);
        self::assertIsArray($result);
        self::assertSame(self::CITY_VALID, $result['id']);

        self::assertIsArray($result['units']);
        $this->validateResult($result, true);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testCurrentInvalid(): void
    {
        self::assertNotNull($this->service);
        $result = $this->service->current(self::CITY_INVALID);
        self::assertFalse($result);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDaily(): void
    {
        $count = 5;
        self::assertNotNull($this->service);
        $result = $this->service->daily(self::CITY_VALID, $count);
        self::assertIsArray($result);
        self::assertSame($count, $result['cnt']);

        /** @psalm-var array $list */
        $list = $result['list'];
        self::assertCount($count, $list);
        self::assertArrayHasKey(0, $list);

        $units = $result['units'];
        self::assertIsArray($units);

        $city = $result['city'];
        self::assertIsArray($city);

        self::assertIsArray($city['coord']);
        $this->validateCoord($city['coord']);

        /** @psalm-var array{sunrise: int|null, sunset: int|null}  $result */
        $result = $list[0];
        self::assertIsInt($result['sunrise']);
        self::assertIsInt($result['sunset']);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDailyInvalid(): void
    {
        self::assertNotNull($this->service);
        $result = $this->service->daily(self::CITY_INVALID);
        self::assertFalse($result);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testGroup(): void
    {
        self::assertNotNull($this->service);
        $cityIds = [self::CITY_VALID];
        $result = $this->service->group($cityIds);

        self::assertIsArray($result);

        /** @psalm-var int $cnt */
        $cnt = $result['cnt'];
        self::assertSame(1, $cnt);

        /** @psalm-var array<int, array> $list */
        $list = $result['list'];
        self::assertCount(1, $list);

        $units = $result['units'];
        self::assertNotEmpty($units); // @phpstan-ignore-line

        $firstList = $list[0] ?? null;
        self::assertIsArray($firstList);
        self::assertNotEmpty($firstList);
        $this->validateResult($firstList, false);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testGroupInvalidCount(): void
    {
        self::assertNotNull($this->service);
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
        self::assertSame(2, \strlen($data['country']));
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
