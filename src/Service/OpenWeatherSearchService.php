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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to search cities for OpenWeatherMap.
 *
 * @psalm-import-type OpenWeatherCityType from OpenWeatherDatabase
 */
class OpenWeatherSearchService
{
    /**
     * The number of search results to return.
     */
    final public const DEFAULT_LIMIT = 15;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/openweather.sqlite')]
        private readonly string $databaseName,
        private readonly OpenWeatherFormatter $formatter,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Finds a city by for the given identifier.
     *
     * @param int $id the identifier to get city for
     *
     * @return array|false the city, if found; false otherwise
     *
     * @psalm-return OpenWeatherCityType|false
     */
    public function findById(int $id): array|false
    {
        $db = null;

        try {
            $db = new OpenWeatherDatabase($this->databaseName, true);

            return $db->findById($id);
        } finally {
            $db?->close();
        }
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
     * @psalm-return array<int, OpenWeatherCityType>
     */
    public function search(string $name, int $limit = self::DEFAULT_LIMIT): array
    {
        $key = 'OpenWeatherSearchService?' . \http_build_query(['name' => $name, 'limit' => $limit]);

        return $this->cache->get($key, fn (): array => $this->doSearch($name, $limit));
    }

    /**
     * @psalm-return array<int, OpenWeatherCityType>
     */
    private function doSearch(string $name, int $limit): array
    {
        $db = null;

        try {
            $db = new OpenWeatherDatabase($this->databaseName, true);

            $results = $db->findCity($name, $limit);
            if ([] === $results) {
                return [];
            }

            $this->formatter->update($results);

            /** @psalm-var array<int, OpenWeatherCityType> */
            return $results;
        } finally {
            $db?->close();
        }
    }
}
