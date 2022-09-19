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

use App\Database\SwissDatabase;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service to search zip codes, cities and streets from Switzerland.
 */
class SwissPostService
{
    /**
     * Constructor.
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/swiss.sqlite')]
        private readonly string $databaseName
    ) {
    }

    /**
     * Finds values by searching in streets, zip codes and cities.
     *
     * @param string $value the value to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching values
     */
    public function findAll(string $value, int $limit = 25): array
    {
        $db = $this->getDatabase(true);
        $result = $db->findAll($value, $limit);
        $db->close();

        return $result;
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
     * @return array an array, maybe empty, of matching streets
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
     * @return array an array, maybe empty, of matching zip codes
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
        return $this->databaseName;
    }
}
