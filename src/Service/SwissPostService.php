<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Database\SwissDatabase;

/**
 * Service to import zip code and city from Switzerland.
 *
 * @author Laurent Muller
 */
class SwissPostService
{
    /**
     * The database name.
     */
    public const DATABASE_NAME = 'swiss.sqlite';

    /**
     * The relative path to data.
     */
    private const DATA_PATH = '/resources/data/';

    /**
     * The data diretory.
     */
    private string $dataDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $projectDir)
    {
        $this->dataDirectory = $projectDir . self::DATA_PATH;
    }

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
        $db = $this->getDatabase(true);
        $result = $db->findCity($name, $limit);
        $db->close();

        return $result;
    }

    /**
     * Finds street by name.
     *
     * @param string $name  the name to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findStreet(string $name, int $limit = 25): array
    {
        $db = $this->getDatabase(true);
        $result = $db->findStreet($name, $limit);
        $db->close();

        return $result;
    }

    /**
     * Finds cities by zip code.
     *
     * @param string $zip   the zip code to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findZip(string $zip, int $limit = 25): array
    {
        $db = $this->getDatabase(true);
        $result = $db->findZip($zip, $limit);
        $db->close();

        return $result;
    }

    /**
     * Gets the database.
     *
     * @param bool $readonly true open the database for reading only
     *
     * @return SwissDatabase the database
     */
    public function getDatabase(bool $readonly = false): SwissDatabase
    {
        return new SwissDatabase($this->getDatabaseName(), $readonly);
    }

    /**
     * Gets the database file name.
     */
    public function getDatabaseName(): string
    {
        return $this->dataDirectory . self::DATABASE_NAME;
    }

    /**
     * Gets the data directory.
     *
     * @return string
     */
    public function getDataDirectory(): ?string
    {
        return $this->dataDirectory;
    }
}
