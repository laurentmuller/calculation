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
 * SQLite database for zip codes, cities and streets of Switzerland.
 *
 * @phpstan-type SearchStreetType = array{
 *       street: string,
 *       zip: int,
 *       city: string,
 *       state: string,
 *       display: string}
 * @phpstan-type SearchZipCityType = array{
 *       zip: int,
 *       city: string,
 *       state: string,
 *       display: string}
 */
class SwissDatabase extends AbstractDatabase
{
    /**
     * SQL statement to create the city table.
     *
     * @var string
     */
    private const CREATE_CITY = <<<'sql'
        CREATE TABLE IF NOT EXISTS city (
            id       INTEGER PRIMARY KEY,
            zip      INTEGER NOT NULL,
            name     TEXT NOT NULL,
            state_id TEXT NOT NULL
        )
        sql;

    /**
     * SQL statement to create the state (canton) table.
     *
     * @var string
     */
    private const CREATE_STATE = <<<'sql'
        CREATE TABLE IF NOT EXISTS state (
            id      TEXT PRIMARY KEY,
            name    TEXT NOT NULL
        )
        sql;

    /**
     * SQL statement to create the street table.
     *
     * @var string
     */
    private const CREATE_STREET = <<<'sql'
        CREATE TABLE IF NOT EXISTS street (
            city_id INTEGER NOT NULL,
            name    TEXT NOT NULL,
            FOREIGN KEY(city_id) REFERENCES city(id)
        )
        sql;

    /**
     * SQL statement to add a city into the table.
     *
     * @var string
     */
    private const INSERT_CITY = <<<'sql'
        INSERT INTO city(id, zip, name, state_id)
            VALUES(:id, :zip, :name, :state_id)
        sql;

    /**
     * SQL statement to add a state into the table.
     *
     * @var string
     */
    private const INSERT_STATE = <<<'sql'
        INSERT INTO state(id, name)
            VALUES(:id, :name)
        sql;

    /**
     * SQL statement to add a street into the table.
     *
     * @var string
     */
    private const INSERT_STREET = <<<'sql'
        INSERT INTO street(city_id, name)
            VALUES(:city_id, :name)
        sql;

    /**
     * SQL statement to find by multiple criterias.
     *
     * @var string
     */
    private const SEARCH = <<<'sql'
        SELECT
            street.name as street,
            city.zip,
            city.name as city,
            state.name as state,
            printf('%s, %s %s', street.name, city.zip, city.name) as display
        FROM street
        INNER JOIN city on street.city_id = city.id
        INNER JOIN state on city.state_id = state.id
        WHERE
            street.name LIKE :street
            AND
            city.zip LIKE :zip
            AND
            city.name LIKE :city
        ORDER BY
            street.name,
            city.zip,
            city.name
        LIMIT :limit
        sql;

    /**
     * SQL statement to find all.
     *
     * @var string
     */
    private const SEARCH_ALL = <<<'sql'
        SELECT
            street.name as street,
            city.zip,
            city.name as city,
            state.name as state,
            printf('%s, %s %s', street.name, city.zip, city.name) as display
        FROM street
        INNER JOIN city on street.city_id = city.id
        INNER JOIN state on city.state_id = state.id
        WHERE
            street.name LIKE :value
            OR
            city.zip LIKE :value
            OR
            city.name LIKE :value
        ORDER BY
            street.name,
            city.zip,
            city.name
        LIMIT :limit
        sql;
    /**
     * SQL statement to find a city.
     *
     * @var string
     */
    private const SEARCH_CITY = <<<'sql'
        SELECT
            city.zip,
            city.name as city,
            state.name as state,
            printf('%s, %s', city.name, city.zip) as display
        FROM city
        INNER JOIN state on city.state_id = state.id
        WHERE city.name LIKE :value
        ORDER BY
            city.name,
            city.zip
        LIMIT :limit
        sql;

    /**
     * SQL statement to find a street.
     *
     * @var string
     */
    private const SEARCH_STREET = <<<'sql'
        SELECT
            street.name as street,
            city.zip,
            city.name as city,
            state.name as state,
            printf('%s, %s %s', street.name, city.zip, city.name) as display
        FROM street
        INNER JOIN city on street.city_id = city.id
        INNER JOIN state on city.state_id = state.id
        WHERE street.name LIKE :value
        ORDER BY
            street.name,
            city.zip,
            city.name
        LIMIT :limit
        sql;

    /**
     * SQL statement to find a zip code.
     *
     * @var string
     */
    private const SEARCH_ZIP = <<<'sql'
        SELECT
            city.zip,
            city.name as city,
            state.name as state,
            printf('%s %s', city.zip, city.name) as display
        FROM city
        INNER JOIN state on city.state_id = state.id
        WHERE city.zip LIKE :value
        ORDER BY
            city.zip,
            city.name
        LIMIT :limit
        sql;

    /**
     * Finds values by the given parameters.
     *
     * @param array{zip:string, city: string, street: string} $parameters the search parameters
     * @param int                                             $limit      the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching values
     *
     * @phpstan-return SearchStreetType[]
     */
    public function find(array $parameters, int $limit = 25): array
    {
        if ([] === \array_filter($parameters)) {
            return [];
        }

        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::SEARCH);
        $stmt->bindValue(':zip', $this->likeValue($parameters['zip']));
        $stmt->bindValue(':city', $this->likeValue($parameters['city']));
        $stmt->bindValue(':street', $this->likeValue($parameters['street']));
        $stmt->bindValue(':limit', $limit, \SQLITE3_INTEGER);

        /** @psalm-var SearchStreetType[] */
        return $this->executeAndFetch($stmt);
    }

    /**
     * Finds values by searching in streets, zip codes and cities.
     *
     * @param string $value the value to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching values
     *
     * @phpstan-return SearchStreetType[]
     */
    public function findAll(string $value, int $limit = 25): array
    {
        /** @psalm-var SearchStreetType[] */
        return $this->search(self::SEARCH_ALL, $value, $limit);
    }

    /**
     * Finds cities.
     *
     * @param string $city  the name of the city to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     *
     * @phpstan-return SearchZipCityType[]
     */
    public function findCity(string $city, int $limit = 25): array
    {
        /** @psalm-var SearchZipCityType[] */
        return $this->search(self::SEARCH_CITY, $city, $limit);
    }

    /**
     * Finds streets.
     *
     * @param string $street the name of the street to search for
     * @param int    $limit  the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     *
     * @phpstan-return SearchStreetType[]
     */
    public function findStreet(string $street, int $limit = 25): array
    {
        /** @psalm-var SearchStreetType[] */
        return $this->search(self::SEARCH_STREET, $street, $limit);
    }

    /**
     * Finds zip codes.
     *
     * @param string $zip   the zip code to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     *
     * @phpstan-return SearchZipCityType[]
     */
    public function findZip(string $zip, int $limit = 25): array
    {
        /** @psalm-var SearchZipCityType[] */
        return $this->search(self::SEARCH_ZIP, $zip, $limit);
    }

    /**
     * Gets the record's count for all tables.
     *
     * @return array{state: int, city: int, street: int}
     */
    public function getTablesCount(): array
    {
        return [
            'state' => $this->getRecordsCount('state'),
            'city' => $this->getRecordsCount('city'),
            'street' => $this->getRecordsCount('street'),
        ];
    }

    /**
     * Insert a city.
     *
     * The data has the following meaning:
     *
     * - 0: The city identifier (primary key).
     * - 1: The zip code.
     * - 2: The city name.
     * - 3: The state (canton).
     *
     * @param array{0: int, 1: int, 2: string, 3:string} $data the data to insert
     *
     * @return bool true if success
     */
    public function insertCity(array $data): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::INSERT_CITY);

        // parameters
        $stmt->bindValue(':id', $data[0], \SQLITE3_INTEGER);
        $stmt->bindValue(':zip', $data[1], \SQLITE3_INTEGER);
        $stmt->bindValue(':name', $data[2]);
        $stmt->bindValue(':state_id', $data[3]);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * Insert a state.
     *
     * The data has the following meaning:
     *
     *  - 0: The state identifier (primary key).
     *  - 1: The state name.
     *
     * @phpstan-param array{0: string, 1: string} $data the data to insert
     *
     * @return bool true if success
     */
    public function insertState(array $data): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::INSERT_STATE);

        // parameters
        $stmt->bindValue(':id', $data[0]);
        $stmt->bindValue(':name', $data[1]);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * Insert a street.
     *
     *  The data has the following meaning:
     *
     *  - 0: The city identifier (foreign key).
     *  - 1: The street name.
     *
     * @phpstan-param array{0: int, 1: string} $data the data to insert
     *
     * @return bool true if success
     */
    public function insertStreet(array $data): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::INSERT_STREET);

        // parameters
        $stmt->bindValue(':city_id', $data[0], \SQLITE3_INTEGER);
        $stmt->bindValue(':name', $data[1]);

        // execute
        return false !== $stmt->execute();
    }

    #[\Override]
    protected function createSchema(): void
    {
        // tables
        $this->exec(self::CREATE_STATE);
        $this->exec(self::CREATE_CITY);
        $this->exec(self::CREATE_STREET);

        // indexes
        $this->createIndex('city', 'name');
        $this->createIndex('city', 'zip');
        $this->createIndex('street', 'name');
    }
}
