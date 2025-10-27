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

use App\Service\OpenWeatherFormatter;
use App\Service\OpenWeatherSearchService;
use App\Service\PositionService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class OpenWeatherSearchServiceTest extends TestCase
{
    use TranslatorMockTrait;

    private string $databaseName;
    private OpenWeatherSearchService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->databaseName = __DIR__ . '/../files/sqlite/openweather_test.sqlite';
        $service = new PositionService($this->createMockTranslator());
        $formatter = new OpenWeatherFormatter($service);
        $cache = new ArrayAdapter();
        $this->service = new OpenWeatherSearchService($this->databaseName, $formatter, $cache);
    }

    public function testGetDatabaseName(): void
    {
        $actual = $this->service->getDatabaseName();
        self::assertSame($this->databaseName, $actual);
    }

    public function testSearchEmpty(): void
    {
        $actual = $this->service->search('fake');
        self::assertEmpty($actual);
    }

    public function testSearchSuccess(): void
    {
        $cities = $this->service->search('Fribourg');
        self::assertCount(3, $cities);

        $actual = $cities[0];
        // @phpstan-ignore staticMethod.impossibleType
        self::assertArrayHasKey('lat_dms', $actual);
        self::assertArrayHasKey('lon_dms', $actual);
        self::assertArrayHasKey('lat_lon_dms', $actual);
        self::assertArrayHasKey('lat_lon_url', $actual);
        self::assertArrayHasKey('country_name', $actual);
        self::assertArrayHasKey('country_flag', $actual);
    }
}
