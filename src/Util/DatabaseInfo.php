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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to get database information.
 *
 * @author Laurent Muller
 */
final class DatabaseInfo
{
    /**
     * Constructor.
     */
    public function __construct(private EntityManagerInterface $manager)
    {
    }

    /**
     * Gets database variables.
     *
     * @return array an array of variables with name as key and value
     */
    public function getConfiguration(): array
    {
        $values = [];

        try {
            $sql = 'SHOW VARIABLES';
            $result = $this->executeQuery($sql);
            $entries = $result->fetchAllAssociative();
            $result->free();

            // convert
            foreach ($entries as $entry) {
                if ('' !== $entry['Value']) {
                    $key = (string) $entry['Variable_name'];
                    $values[$key] = (string) $entry['Value'];
                }
            }
        } catch (\Exception) {
        }

        return $values;
    }

    /**
     * Gets the database server information.
     *
     * @psalm-suppress InternalMethod
     */
    public function getDatabase(): array
    {
        $result = [];

        try {
            $params = $this->getConnection()->getParams();
            foreach (['dbname', 'host', 'port', 'driver'] as $key) {
                $result[$key] = $params[$key] ?? null;
            }

            // @phpstan-ignore-next-line
            return \array_filter($result, function ($value): bool {
                return Utils::isString($value);
            });
        } catch (\Exception) {
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
            $result = $this->executeQuery($sql);
            $entries = $result->fetchAssociative();
            $result->free();

            if (false !== $entries) {
                return (string) $entries['Value'];
            }
        } catch (\Exception) {
        }

        return 'Unknown';
    }

    /**
     * Prepares an SQL statement and return the result.
     */
    private function executeQuery(string $sql): Result
    {
        $connection = $this->getConnection();
        /** @psalm-var \Doctrine\DBAL\Statement $statement */
        $statement = $connection->prepare($sql);

        return $statement->executeQuery();
    }

    /**
     * Gets the connection.
     */
    private function getConnection(): Connection
    {
        return $this->manager->getConnection();
    }
}
