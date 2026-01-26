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

use App\Utils\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to get database information.
 */
class DatabaseInfoService
{
    private const array DISABLED_VALUES = ['off', 'no', 'false', 'disabled'];
    private const array ENABLED_VALUES = ['on', 'yes', 'true', 'enabled'];

    /** @var array<string, string>|null */
    private ?array $configuration = null;

    /** @var array<string, string>|null */
    private ?array $database = null;

    private ?string $version = null;

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
        if (null === $this->configuration) {
            $this->configuration = [];

            try {
                /** @var array<array{Variable_name: string, Value: string}> $entries */
                $entries = $this->executeQuery('SHOW VARIABLES', true);
                foreach ($entries as $entry) {
                    $value = $entry['Value'];
                    if ('' === $value) {
                        continue;
                    }
                    $this->configuration[$entry['Variable_name']] = match ($value) {
                        'ON', 'OFF',
                        'YES', 'NO',
                        'ENABLED', 'DISABLED',
                        'AUTO',
                        'AUTOMATIC' => StringUtils::capitalize($value),
                        default => $value
                    };
                }
            } catch (\Exception|Exception) {
            }
        }

        return $this->configuration;
    }

    /**
     * Gets the database information.
     *
     * @return array<string, string>
     */
    public function getDatabase(): array
    {
        if (null === $this->database) {
            $this->database = ['Server' => 'MariaDB'];

            try {
                $params = $this->getConnection()->getParams();
                foreach (['serverVersion', 'dbname', 'host', 'port', 'driver', 'charset'] as $key) {
                    $value = $params[$key] ?? null;
                    if (\is_scalar($value)) {
                        $key = match ($key) {
                            'dbname' => 'Name',
                            'serverVersion' => 'Version',
                            default => \ucfirst($key)
                        };
                        $this->database[$key] = (string) $value;
                    }
                }
            } catch (\Exception) {
            }
        }

        return $this->database;
    }

    /**
     * Gets database version.
     */
    public function getVersion(): string
    {
        if (null === $this->version) {
            $this->version = 'Unknown';

            try {
                $entries = $this->executeQuery('SHOW VARIABLES LIKE "version"', false);
                if (false !== $entries) {
                    $value = (string) $entries['Value'];
                    $this->version = \explode('-', $value)[0];
                }
            } catch (\Exception|Exception) {
            }
        }

        return $this->version;
    }

    /**
     * Returns if the given value represents a disabled value.
     */
    public function isDisabledValue(string $value): bool
    {
        return \in_array(\strtolower($value), self::DISABLED_VALUES, true);
    }

    /**
     * Returns if the given value represents an enabled value.
     */
    public function isEnabledValue(string $value): bool
    {
        return \in_array(\strtolower($value), self::ENABLED_VALUES, true);
    }

    /**
     * Prepares an SQL statement and return the result.
     *
     * @throws Exception
     */
    private function executeQuery(string $sql, bool $all): array|false
    {
        $connection = $this->getConnection();
        $statement = $connection->prepare($sql);
        $result = $statement->executeQuery();
        $entries = $all ? $result->fetchAllAssociative() : $result->fetchAssociative();
        $result->free();

        return $entries;
    }

    /**
     * Gets the connection.
     */
    private function getConnection(): Connection
    {
        return $this->manager->getConnection();
    }
}
