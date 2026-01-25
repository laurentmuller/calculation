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

namespace App\Database;

/**
 * Database to search cites for OpenWeatherMap.
 *
 * @phpstan-type OpenWeatherCityType = array{
 *      id: int,
 *      name: string,
 *      country: string,
 *      latitude: float,
 *      longitude: float}
 */
class OpenWeatherDatabase extends AbstractDatabase implements \Countable
{
    /**
     * SQL statement to create the city table.
     */
    private const CREATE_CITY = <<<'SQL'
        CREATE TABLE IF NOT EXISTS city (
            id        INTEGER PRIMARY KEY,
            name      TEXT NOT NULL,
            country   TEXT NOT NULL,
            latitude  REAL NOT NULL,
            longitude REAL NOT NULL
        )
        SQL;

    /**
     * SQL statement to delete all cities.
     */
    private const DELETE_CITIES = 'DELETE FROM city';

    /**
     * SQL statement to add a city into the table.
     */
    private const INSERT_CITY = <<<'SQL'
        INSERT INTO city(id, name, country, latitude, longitude)
            VALUES(:id, :name, :country, :latitude, :longitude)
        SQL;

    /**
     * SQL statement to find a city for the given identifier.
     */
    private const SEARCH_BY_ID = <<<'SQL'
        SELECT
            id,
            name,
            country,
            latitude,
            longitude
        FROM city
        WHERE id = :id
        SQL;

    /**
     * SQL statement to find a city.
     */
    private const SEARCH_CITY = <<<'SQL'
        SELECT
            id,
            name,
            country,
            latitude,
            longitude
        FROM city
        WHERE name LIKE :value
        ORDER BY
            name
        LIMIT :limit
        SQL;

    /**
     * SQL statement to find a city.
     */
    private const SEARCH_CITY_COUNTRY = <<<'SQL'
        SELECT
            id,
            name,
            country,
            latitude,
            longitude
        FROM city
        WHERE name LIKE :name AND country LIKE :country
        ORDER BY
            name
        LIMIT :limit
        SQL;

    #[\Override]
    public function count(): int
    {
        return $this->getRecordsCount('city');
    }

    /**
     * Delete all cities.
     *
     * @return bool true on success
     */
    public function deleteCities(): bool
    {
        return $this->exec(self::DELETE_CITIES);
    }

    /**
     * Finds a city by for the given identifier.
     *
     * @param int $id the identifier to get city for
     *
     * @return array|false the city, if found; false otherwise
     *
     * @phpstan-return OpenWeatherCityType|false
     */
    public function findById(int $id): array|false
    {
        /** @phpstan-var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::SEARCH_BY_ID);
        $stmt->bindValue(':id', $id);
        /** @phpstan-var OpenWeatherCityType[] $result */
        $result = $this->executeAndFetch($stmt);

        return [] === $result ? false : \reset($result);
    }

    /**
     * Finds cities by name.
     *
     * @param string $name  the name to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @phpstan-return OpenWeatherCityType[]
     */
    public function findCity(string $name, int $limit = 25): array
    {
        $values = \explode(',', $name);
        if (2 === \count($values)) {
            return $this->findCityCountry($values[0], $values[1], $limit);
        }

        /** @phpstan-var OpenWeatherCityType[] */
        return $this->search(self::SEARCH_CITY, $name, $limit);
    }

    /**
     * Finds cities by name and country.
     *
     * @param string $city    the city to search for
     * @param string $country the country to search for
     * @param int    $limit   the maximum number of rows to return
     *
     * @phpstan-return OpenWeatherCityType[]
     */
    public function findCityCountry(string $city, string $country, int $limit = 25): array
    {
        /** @phpstan-var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::SEARCH_CITY_COUNTRY);
        $stmt->bindValue(':name', $this->likeValue($city));
        $stmt->bindValue(':country', $this->likeValue($country));
        $stmt->bindValue(':limit', $limit, \SQLITE3_INTEGER);

        /** @phpstan-var OpenWeatherCityType[] */
        return $this->executeAndFetch($stmt);
    }

    /**
     * Insert a city.
     *
     * @param int    $id        the city identifier
     * @param string $name      the city name
     * @param string $country   the two-letter ISO code of the country
     * @param float  $latitude  the city latitude
     * @param float  $longitude the city longitude
     *
     * @return bool true if success
     */
    public function insertCity(int $id, string $name, string $country, float $latitude, float $longitude): bool
    {
        /** @phpstan-var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::INSERT_CITY);

        $stmt->bindValue(':id', $id, \SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':country', $country);
        $stmt->bindValue(':latitude', $latitude, \SQLITE3_FLOAT);
        $stmt->bindValue(':longitude', $longitude, \SQLITE3_FLOAT);

        // execute
        return false !== $stmt->execute();
    }

    #[\Override]
    protected function createSchema(): void
    {
        $this->exec(self::CREATE_CITY);
        $this->createIndex('city', 'name');
    }
}
