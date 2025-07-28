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
use App\Traits\CacheKeyTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to search cities for OpenWeatherMap.
 *
 * @phpstan-import-type OpenWeatherCityType from OpenWeatherDatabase
 */
class OpenWeatherSearchService
{
    use CacheKeyTrait;

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
     * Finds a city for the given identifier.
     *
     * @param int $id the city identifier to search for
     *
     * @return array|false the city, if found; false otherwise
     *
     * @phpstan-return OpenWeatherCityType|false
     */
    public function findById(int $id): array|false
    {
        return $this->call(static fn (OpenWeatherDatabase $db): array|false => $db->findById($id));
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
     * @phpstan-return array<int, OpenWeatherCityType>
     */
    public function search(string $name, int $limit = self::DEFAULT_LIMIT): array
    {
        $key = $this->cleanKey('OpenWeatherSearchService?' . \http_build_query(['name' => $name, 'limit' => $limit]));

        return $this->cache->get($key, fn (): array => $this->doSearch($name, $limit));
    }

    /**
     * @template TResult
     *
     * @param callable(OpenWeatherDatabase): TResult $callable
     *
     * @return TResult
     */
    private function call(callable $callable): mixed
    {
        $db = null;

        try {
            $db = new OpenWeatherDatabase($this->databaseName, true);

            return $callable($db);
        } finally {
            $db?->close();
        }
    }

    /**
     * @phpstan-return array<int, OpenWeatherCityType>
     */
    private function doSearch(string $name, int $limit): array
    {
        return $this->call(function (OpenWeatherDatabase $db) use ($name, $limit): array {
            $results = $db->findCity($name, $limit);
            if ([] === $results) {
                return [];
            }

            $this->formatter->update($results);

            /** @phpstan-var array<int, OpenWeatherCityType> */
            return $results;
        });
    }
}
