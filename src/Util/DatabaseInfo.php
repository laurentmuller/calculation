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

namespace App\Util;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to get database informations.
 *
 * @author Laurent Muller
 */
final class DatabaseInfo
{
    private EntityManagerInterface $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Gets database variables.
     *
     * @return array an array of variables with name as key and value
     */
    public function getConfiguration(): array
    {
        $result = [];

        try {
            $sql = 'SHOW VARIABLES';
            $connection = $this->getConnection();
            $statement = $connection->prepare($sql);
            $entries = $statement->executeQuery()->fetchAllAssociative();
            $statement->free();

            // convert
            foreach ($entries as $entry) {
                if (0 !== \strlen($entry['Value'])) {
                    $key = $entry['Variable_name'];
                    $result[$key] = $entry['Value'];
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        return $result;
    }

    /**
     * Gets the database server informations.
     */
    public function getDatabase(): array
    {
        $result = [];

        try {
            $params = $this->getConnection()->getParams();
            foreach (['dbname', 'host', 'port', 'driver'] as $key) {
                $result[$key] = $params[$key] ?? null;
            }

            /*
             * @psalm-param mixed $value
             */
            return \array_filter($result, function ($value): bool {
                return Utils::isString($value);
            });
        } catch (\Exception $e) {
            // ignore
        }

        return $result;
    }

    /**
     * Gets database version.
     */
    public function getVersion(): string
    {
        try {
            $sql = 'SHOW VARIABLES LIKE "version"';
            $connection = $this->getConnection();
            $statement = $connection->prepare($sql);
            $result = $statement->executeQuery()->fetchAssociative();
            $statement->free();

            if (false !== $result) {
                return $result['Value'];
            }
        } catch (\Exception $e) {
            // ignore
        }

        return 'Unknown';
    }

    /**
     * Gets the connection.
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        return $this->manager->getConnection();
    }
}
