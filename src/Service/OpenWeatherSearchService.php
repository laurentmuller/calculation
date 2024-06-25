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

namespace App\Service;

use App\Database\OpenWeatherDatabase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to search cities.
 *
 * @psalm-import-type OpenWeatherCityType from OpenWeatherDatabase
 */
class OpenWeatherSearchService
{
    /**
     * The number of search results to return.
     */
    final public const DEFAULT_LIMIT = 15;

    /**
     * The country flag URL.
     */
    private const COUNTRY_URL = 'https://openweathermap.org/images/flags/{0}.png';

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/openweather.sqlite')]
        private readonly string $databaseName,
        private readonly PositionService $service,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Gets the database name.
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Search cities.
     *
     * @param string $name  the name of the city to search for
     * @param int    $limit the maximum number of cities to return
     *
     * @pslam-return array<int, OpenWeatherCityType>
     */
    public function search(string $name, int $limit = self::DEFAULT_LIMIT): array
    {
        $key = 'OpenWeatherSearchService?' . \http_build_query(['name' => $name, 'limit' => $limit]);

        try {
            return $this->cache->get($key, fn (): array => $this->doSearch($name, $limit));
        } catch (InvalidArgumentException) {
            return [];
        }
    }

    /**
     * @pslam-return array<int, OpenWeatherCityType>
     */
    private function doSearch(string $name, int $limit): array
    {
        $db = null;

        try {
            $db = new OpenWeatherDatabase($this->databaseName, true);

            /** @psalm-var array<int, OpenWeatherCityType> $result */
            $result = $db->findCity($name, $limit);
            if ([] === $result) {
                return [];
            }

            $this->updateValues($result);

            return $result;
        } finally {
            $db?->close();
        }
    }

    /**
     * Gets the country name from the alpha2 code.
     */
    private function getCountryName(string $country): string
    {
        try {
            return Countries::getName($country);
        } catch (MissingResourceException) {
            return '';
        }
    }

    private function replaceUrl(string $country): string
    {
        return \str_replace('{0}', $country, self::COUNTRY_URL);
    }

    private function updateCoordinate(array &$value): void
    {
        /** @psalm-var float $lat */
        $lat = $value['latitude'];
        /** @psalm-var float $lon */
        $lon = $value['longitude'];
        $value['lat_dms'] = $this->service->formatLatitude($lat);
        $value['lon_dms'] = $this->service->formatLongitude($lon);
        $value['lat_lon_dms'] = $this->service->formatPosition($lat, $lon);
        $value['lat_lon_url'] = $this->service->getGoogleMapUrl($lat, $lon);
    }

    private function updateCountry(array &$value): void
    {
        /** @psalm-var string $country */
        $country = $value['country'];
        $value['country_name'] = $this->getCountryName($country);
        $value['country_flag'] = $this->replaceUrl(\strtolower($country));
    }

    /**
     * @psalm-param array<int, OpenWeatherCityType> $results
     */
    private function updateValues(array &$results): void
    {
        /** @psalm-var array $value */
        foreach ($results as &$value) {
            $this->updateCoordinate($value);
            $this->updateCountry($value);
        }
    }
}
