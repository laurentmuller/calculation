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
use App\Service\PositionService;
use App\Tests\TranslatorMockTrait;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

class OpenWeatherFormatterTest extends TestCase
{
    use TranslatorMockTrait;

    private OpenWeatherFormatter $formatter;

    protected function setUp(): void
    {
        $service = new PositionService($this->createMockTranslator());
        $this->formatter = new OpenWeatherFormatter($service);
    }

    public function testUpdateCoordinate(): void
    {
        $results = [
            ['lat' => 0.0, 'lon' => 0.0],
        ];
        $this->formatter->update($results);
        self::assertIsArray($results[0]);
        $actual = $results[0];
        self::assertArrayHasKey('lat_dms', $actual);
        self::assertArrayHasKey('lon_dms', $actual);
        self::assertArrayHasKey('lat_lon_dms', $actual);
        self::assertArrayHasKey('lat_lon_url', $actual);
    }

    public function testUpdateCountry(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $results = [
            'country' => 'CH',
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('country_name', $results);
        self::assertArrayHasKey('country_flag', $results);
        self::assertIsString($results['country_name']);
        self::assertIsString($results['country_flag']);
        self::assertSame('Suisse', $results['country_name']);
        self::assertSame('https://openweathermap.org/images/flags/ch.png', $results['country_flag']);
    }

    public function testUpdateCountryInvalid(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $results = [
            'country' => 'fake',
        ];
        $this->formatter->update($results);
        self::assertArrayNotHasKey('country_name', $results);
        self::assertArrayNotHasKey('country_flag', $results);
    }

    public function testUpdateDate(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $results = [
            'dt' => \time(),
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('dt_date', $results);
        self::assertArrayHasKey('dt_date_locale', $results);
        self::assertArrayHasKey('dt_time', $results);
        self::assertArrayHasKey('dt_time_locale', $results);
        self::assertArrayHasKey('dt_date_time', $results);
        self::assertArrayHasKey('dt_date_time_locale', $results);
        self::assertArrayHasKey('dt_date_time_medium', $results);
        self::assertArrayHasKey('dt_date_time_medium_locale', $results);
        self::assertArrayHasKey('dt_date_time_long', $results);
        self::assertArrayHasKey('dt_date_time_long_locale', $results);
    }

    public function testUpdateDeg(): void
    {
        $results = [
            'deg' => 25,
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('deg_direction', $results);
        self::assertArrayHasKey('deg_description', $results);
    }

    public function testUpdateDescription(): void
    {
        $results = [
            'description' => 'description',
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('description', $results);
        self::assertIsString($results['description']);
        self::assertSame('Description', $results['description']);
    }

    public function testUpdateIcon(): void
    {
        $results = [
            'icon' => 'icon',
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('icon_big', $results);
        self::assertArrayHasKey('icon_small', $results);
        self::assertIsString($results['icon_big']);
        self::assertIsString($results['icon_small']);
        self::assertSame('https://openweathermap.org/img/wn/icon@4x.png', $results['icon_big']);
        self::assertSame('https://openweathermap.org/img/wn/icon@2x.png', $results['icon_small']);
    }

    public function testUpdateLat(): void
    {
        $results = [
            'lat' => 0.0,
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('lat_dms', $results);
        self::assertIsString($results['lat_dms']);
    }

    public function testUpdateLatitude(): void
    {
        $results = [
            'latitude' => 0.0,
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('lat_dms', $results);
        self::assertIsString($results['lat_dms']);
    }

    public function testUpdateLon(): void
    {
        $results = [
            'lon' => 0.0,
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('lon_dms', $results);
        self::assertIsString($results['lon_dms']);
    }

    public function testUpdateLongitude(): void
    {
        $results = [
            'longitude' => 0.0,
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('lon_dms', $results);
        self::assertIsString($results['lon_dms']);
    }

    public function testUpdateSunrise(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $results = [
            'sunrise' => \time(),
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('sunrise_formatted', $results);
        self::assertIsString($results['sunrise_formatted']);
    }

    public function testUpdateSunset(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $results = [
            'sunset' => \time(),
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('sunset_formatted', $results);
        self::assertIsString($results['sunset_formatted']);
    }

    public function testUpdateTimeZone(): void
    {
        $timezone = new \DateTimeZone('UTC');
        $results = [
            'timezone' => $timezone,
        ];
        $this->formatter->update($results, $timezone);
        self::assertArrayHasKey('timezone_name', $results);
        self::assertIsString($results['timezone_name']);
    }

    public function testUpdateWeather(): void
    {
        $results = [
            'weather' => ['entry' => 'value'],
        ];
        $this->formatter->update($results);
        self::assertArrayHasKey('weather', $results);
    }
}
