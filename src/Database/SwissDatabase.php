<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Database;

/**
 * SQLite database for zip codes, cities and streets of Switzerland.
 *
 * @author Laurent Muller
 */
class SwissDatabase extends AbstractDatabase
{
    /**
     * SQL statement to create the city table.
     *
     * @var string
     */
    private static $CREATE_CITY = <<<'sql'
CREATE TABLE IF NOT EXISTS city (
	id    INTEGER NOT NULL,
	zip	  INTEGER NOT NULL,
	name  TEXT NOT NULL,
	state TEXT NOT NULL,
	PRIMARY KEY(id)
) WITHOUT ROWID
sql;

    /**
     * SQL statement to create the state (canton) table.
     *
     * @var string
     */
    private static $CREATE_STATE = <<<'sql'
CREATE TABLE "state" (
	id	    TEXT NOT NULL,
	name	TEXT NOT NULL,
	PRIMARY KEY(id)
) WITHOUT ROWID
sql;

    /**
     * SQL statement to create the street table.
     *
     * @var string
     */
    private static $CREATE_STREET = <<<'sql'
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
    private static $INSERT_CITY = <<<'sql'
INSERT INTO city(id, zip, name, state)
    VALUES(:id, :zip, :name, :state)
sql;

    /**
     * SQL statement to add a state into the table.
     *
     * @var string
     */
    private static $INSERT_STATE = <<<'sql'
INSERT INTO state(id, name)
    VALUES(:id, :name)
sql;

    /**
     * SQL statement to add a street into the table.
     *
     * @var string
     */
    private static $INSERT_STREET = <<<'sql'
INSERT INTO street(city_id, name)
    VALUES(:city_id, :name)
sql;

    /**
     * SQL statement to find a city.
     *
     * @var string
     */
    private static $SEARCH_CITY = <<<'sql'
SELECT
    name,
	zip,
    state,
    printf('%s (%s)', name, zip) as display
FROM city
WHERE name LIKE :value
ORDER BY 
    name,
    zip
LIMIT :limit
sql;

    /**
     * SQL statement to find a street.
     *
     * @var string
     */
    private static $SEARCH_STREET = <<<'sql'
SELECT
    street.name as street,
    city.zip,
    city.name   as city,
    city.state,
    printf('%s - %s %s', street.name, city.zip, city.name) as display
FROM street
INNER JOIN city on street.city_id = city.id
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
    private static $SEARCH_ZIP = <<<'sql'
SELECT
    zip,
    name,
    state,
    printf('%s %s', zip, name) as display
FROM city
WHERE zip LIKE :value
ORDER BY 
    zip,
    name    
LIMIT :limit
sql;

    /**
     * Finds cities.
     *
     * @param string $city  the name of the city to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findCity(string $city, int $limit = 25): array
    {
        return $this->search(self::$SEARCH_CITY, $city, $limit);
    }

    /**
     * Finds streets.
     *
     * @param string $street the name of the street to search for
     * @param int    $limit  the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findStreet(string $street, int $limit = 25): array
    {
        return $this->search(self::$SEARCH_STREET, $street, $limit);
    }

    /**
     * Finds zip codes.
     *
     * @param string $zip   the zip code to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findZip(string $zip, int $limit = 25): array
    {
        return $this->search(self::$SEARCH_ZIP, $zip, $limit);
    }

    /**
     * Insert a city.
     *
     * @param array $data the data to insert with the following values:
     *                    <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *                    <tr>
     *                    <th>Index</th><th>Type</th><th>Description</th>
     *                    </tr>
     *                    <tr>
     *                    <td>0</td><td>integer</td><td>The city identifier (primary key).</td>
     *                    </tr>
     *                    <tr>
     *                    <td>1</td><td>integer</td><td>The zip code.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>2</td><td>string</td><td>The city name.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>3</td><td>string</td><td>The state (canton).</td>
     *                    </tr>
     *                    </table>
     *
     * @return bool true if success
     */
    public function insertCity(array $data): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::$INSERT_CITY);

        // parameters
        $stmt->bindParam(':id', $data[0], SQLITE3_INTEGER);
        $stmt->bindParam(':zip', $data[1], SQLITE3_INTEGER);
        $stmt->bindParam(':name', $data[2], SQLITE3_TEXT);
        $stmt->bindParam(':state', $data[3], SQLITE3_TEXT);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * Insert a state.
     *
     * @param array $data the data to insert with the following values:
     *                    <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *                    <tr>
     *                    <th>Index</th><th>Type</th><th>Description</th>
     *                    </tr>
     *                    <tr>
     *                    <td>0</td><td>string</td><td>The state identifier (primary key).</td>
     *                    </tr>
     *                    <tr>
     *                    <td>1</td><td>string</td><td>The state name.</td>
     *                    </tr>
     *                    </table>
     *
     * @return bool true if success
     */
    public function insertState(array $data): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::$INSERT_STATE);

        // parameters
        $stmt->bindParam(':id', $data[0], SQLITE3_TEXT);
        $stmt->bindParam(':name', $data[1], SQLITE3_TEXT);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * Insert a street.
     *
     * @param array $data the data to insert with the following values:
     *                    <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *                    <tr>
     *                    <th>Index</th><th>Type</th><th>Description</th>
     *                    </tr>
     *                    <tr>
     *                    <td>0</td><td>integer</td><td>The city identifier (foreign key).</td>
     *                    </tr>
     *                    <tr>
     *                    <td>1</td><td>string</td><td>The street name.</td>
     *                    </tr>
     *                    </table>
     *
     * @return bool true if success
     */
    public function insertStreet(array $data): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::$INSERT_STREET);

        // parameters
        $stmt->bindParam(':city_id', $data[0], SQLITE3_INTEGER);
        $stmt->bindParam(':name', $data[1], SQLITE3_TEXT);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(): void
    {
        // tables
        $this->exec(self::$CREATE_STATE);
        $this->exec(self::$CREATE_CITY);
        $this->exec(self::$CREATE_STREET);

        // indexes
        $this->createIndex('city', 'name');
        $this->createIndex('city', 'zip');
        $this->createIndex('street', 'name');
    }
}
