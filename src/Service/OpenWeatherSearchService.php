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

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/openweather.sqlite')]
        private readonly string $databaseName,
        private readonly OpenWeatherFormatter $formatter,
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
     * @psalm-return array<int, OpenWeatherCityType>
     *
     * @throws InvalidArgumentException
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
