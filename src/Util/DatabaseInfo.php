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

namespace App\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to get database information.
 */
final class DatabaseInfo
{
    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Gets database variables.
     *
     * @return array<string, string>
     */
    public function getConfiguration(): array
    {
        $values = [];

        try {
            $sql = 'SHOW VARIABLES';
            $result = $this->executeQuery($sql);
            /** @psalm-var array<array{Variable_name:string, Value:string}> $entries */
            $entries = $result->fetchAllAssociative();
            $result->free();

            // convert
            foreach ($entries as $entry) {
                $value = $entry['Value'];
                if ('' === $value) {
                    continue;
                }
                $values[$entry['Variable_name']] = match ($value) {
                    'ON', 'OFF',
                    'YES', 'NO',
                    'ENABLED', 'DISABLED',
                    'AUTO',
                    'AUTOMATIC' => Utils::capitalize($value),
                    default => $value
                };
            }
        } catch (\Exception) {
        }

        return $values;
    }

    /**
     * Gets the database information.
     *
     * @return array<string, string>
     */
    public function getDatabase(): array
    {
        $result = [];

        try {
            /** @psalm-suppress InternalMethod */
            $params = $this->getConnection()->getParams();
            foreach (['dbname', 'host', 'port', 'driver'] as $key) {
                $value = $params[$key] ?? null;
                if (\is_string($value) || \is_int($value)) {
                    $result[$key] = (string) $value;
                }
            }
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
     *
     * @throws \Doctrine\DBAL\Exception
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
