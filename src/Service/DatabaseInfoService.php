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
    private const array DATABASE_PARAMETERS = [
        'dbname' => 'Name',
        'serverVersion' => 'Version',
        'host' => 'Host',
        'port' => 'Port',
        'driver' => 'Driver',
        'charset' => 'Charset',
    ];
    private const array DISABLED_VALUES = ['off', 'no', 'false', 'disabled'];
    private const array ENABLED_VALUES = ['on', 'yes', 'true', 'enabled'];

    /** @var array<string, string> */
    private array $configuration = [];

    /** @var array<string, string> */
    private array $database = [];

    private ?string $version = null;

    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Gets the database configuration.
     *
     * @return array<string, string>
     */
    public function getConfiguration(): array
    {
        if ([] === $this->configuration) {
            try {
                $variables = $this->getVariables();
                foreach ($variables as $variable) {
                    $value = $variable['Value'];
                    if ('' !== $value) {
                        $this->configuration[$variable['Variable_name']] = $this->convertValue($value);
                    }
                }
            } catch (\Exception|Exception) {
            }
        }

        return $this->configuration;
    }

    /**
     * Gets the database connection parameters.
     *
     * @return array<string, string>
     */
    public function getDatabase(): array
    {
        if ([] === $this->database) {
            $params = $this->getConnection()->getParams();
            foreach (self::DATABASE_PARAMETERS as $key => $name) {
                if (isset($params[$key]) && \is_scalar($params[$key])) {
                    $this->database[$name] = (string) $params[$key];
                }
            }
        }

        return $this->database;
    }

    /**
     * Gets the database version.
     */
    public function getVersion(): string
    {
        return $this->version ??= $this->getConfiguration()['version'] ?? 'Unknown';
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

    private function convertValue(string $value): string
    {
        return match ($value) {
            'ON', 'OFF',
            'YES', 'NO',
            'ENABLED', 'DISABLED',
            'AUTO', 'AUTOMATIC' => StringUtils::capitalize($value),
            default => $value
        };
    }

    /**
     * Gets the connection.
     */
    private function getConnection(): Connection
    {
        return $this->manager->getConnection();
    }

    /**
     * Gets the database variables.
     *
     * @return array<array{Variable_name: string, Value: string}>
     *
     * @throws Exception
     */
    private function getVariables(): array
    {
        $result = null;

        try {
            $connection = $this->getConnection();
            $statement = $connection->prepare('SHOW VARIABLES;');
            $result = $statement->executeQuery();

            /** @phpstan-var array<array{Variable_name: string, Value: string}> */
            return $result->fetchAllAssociative();
        } finally {
            $result?->free();
        }
    }
}
