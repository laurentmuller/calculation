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
 * Database to search cites for OpenWeatherMap.
 *
 * @author Laurent Muller
 */
class OpenWeatherDatabase extends AbstractDatabase
{
    /**
     * SQL statement to create the city table.
     *
     * @var string
     */
    private static $CREATE_CITY = <<<'sql'
CREATE TABLE city (
	id	      INTEGER NOT NULL,
	name	  TEXT NOT NULL,
	country   TEXT NOT NULL,
	latitude  REAL NOT NULL,
	longitude REAL NOT NULL,
	PRIMARY KEY("id")
) WITHOUT ROWID
sql;

    /**
     * SQL statement to add a city into the table.
     *
     * @var string
     */
    private static $INSERT_CITY = <<<'sql'
INSERT INTO city(id, name, country, latitude, longitude)
    VALUES(:id, :name, :country, :latitude, :longitude)
sql;

    /**
     * SQL statement to find a city.
     *
     * @var string
     */
    private static $SEARCH_CITY = <<<'sql'
SELECT
    id,
    name,
    country
FROM city
WHERE name LIKE :value
ORDER BY
    name
LIMIT :limit
sql;

    /**
     * Finds cities by name.
     *
     * @param string $name  the name to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findCity(string $name, int $limit = 25): array
    {
        return $this->search(self::$SEARCH_CITY, $name, $limit);
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
     *                    <td>1</td><td>string</td><td>The city name.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>2</td><td>string</td><td>The country abreviation.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>3</td><td>double</td><td>The latitude.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>4</td><td>double</td><td>The longitude.</td>
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
        $stmt->bindParam(':name', $data[1], SQLITE3_TEXT);
        $stmt->bindParam(':country', $data[2], SQLITE3_TEXT);
        $stmt->bindParam(':latitude', $data[3], SQLITE3_FLOAT);
        $stmt->bindParam(':longitude', $data[4], SQLITE3_FLOAT);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(): void
    {
        // table
        $this->exec(self::$CREATE_CITY);

        // index
        $this->createIndex('city', 'name');
    }
}
