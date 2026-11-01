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

use App\Constants\CacheAttributes;
use App\Traits\EnablementValueTrait;
use App\Utils\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

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

    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Target(CacheAttributes::CACHE_SYMFONY)]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Gets the database configuration.
     *
     * @return array<string, string>
     */
    public function getConfiguration(): array
    {
        return $this->cache->get('configuration', fn () => $this->loadConfiguration());
    }

    /**
     * Gets the database connection parameters.
     *
     * @return array<string, string>
     */
    public function getDatabase(): array
    {
        return $this->cache->get('database', fn () => $this->loadDatabase());
    }

    /**
     * Gets the database version.
     */
    public function getVersion(): string
    {
        return $this->getConfiguration()['version'] ?? self::UNKNOWN;
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

    /**
     * @return array<string, string>
     */
    private function loadConfiguration(): array
    {
        $result = [];

        try {
            $variables = $this->getVariables();
            foreach ($variables as $variable) {
                $value = StringUtils::trim($variable['Value']);
                if (null === $value) {
                    continue;
                }
                $name = $variable['Variable_name'];
                $result[$name] = $this->convertValue($value, $name);
            }
        } catch (Exception) {
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function loadDatabase(): array
    {
        $result = [];
        $params = $this->getConnection()->getParams();
        foreach (self::DATABASE_PARAMETERS as $key => $name) {
            if (isset($params[$key]) && \is_scalar($params[$key])) {
                $result[$name] = (string) $params[$key];
            }
        }

        return $result;
    }
}
