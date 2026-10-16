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

use App\Traits\EnablementValueTrait;
use App\Utils\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to get database information.
 */
class DatabaseInfoService
{
    use EnablementValueTrait;

    private const array DATABASE_PARAMETERS = [
        'dbname' => 'Name',
        'serverVersion' => 'Version',
        'host' => 'Host',
        'port' => 'Port',
        'driver' => 'Driver',
        'charset' => 'Charset',
    ];

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
                    $value = StringUtils::trim($variable['Value']);
                    if (null === $value) {
                        continue;
                    }
                    $name = $variable['Variable_name'];
                    $this->configuration[$name] = $this->convertValue($value, $name);
                }
            } catch (Exception) {
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

    private function convertValue(string $value, string $name): string
    {
        if ($this->isDisabledValue($value) || $this->isEnabledValue($value)) {
            return StringUtils::capitalize($value);
        }
        if ($this->isNoneValue($value)) {
            return self::NONE_VALUE;
        }
        if ('version' === $name) {
            $index = \strpos($value, '-');
            if (\is_int($index)) {
                return StringUtils::left($value, $index);
            }
        }

        return $value;
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
        $connection = $this->getConnection();
        $statement = $connection->prepare('SHOW VARIABLES;');
        $result = $statement->executeQuery();

        /** @phpstan-var array<array{Variable_name: string, Value: string}> */
        return $result->fetchAllAssociative();
    }
}
