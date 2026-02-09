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

namespace App\Tests\Service;

use App\Service\DatabaseInfoService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertSame;

final class DatabaseInfoServiceTest extends TestCase
{
    private const array PARAMS = [
        'dbname' => 'database',
        'serverVersion' => '10.11.15',
        'host' => 'localhost',
        'port' => 3008,
        'driver' => 'pdo_mysql',
        'charset' => 'utf8mb4',
    ];

    public static function getDisabledValues(): \Generator
    {
        yield ['oFf', true];
        yield ['off', true];
        yield ['no', true];
        yield ['false', true];
        yield ['disabled', true];
        yield ['', false];
        yield ['yes', false];
    }

    public static function getEnabledValues(): \Generator
    {
        yield ['oN', true];
        yield ['on', true];
        yield ['yes', true];
        yield ['true', true];
        yield ['enabled', true];
        yield ['', false];
        yield ['disabled', false];
        yield ['no', false];
    }

    #[DataProvider('getDisabledValues')]
    public function testDisabledValue(string $value, bool $expected): void
    {
        $manager = self::createStub(EntityManagerInterface::class);
        $service = new DatabaseInfoService($manager);
        $actual = $service->isDisabledValue($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getEnabledValues')]
    public function testEnabledValue(string $value, bool $expected): void
    {
        $manager = self::createStub(EntityManagerInterface::class);
        $service = new DatabaseInfoService($manager);
        $actual = $service->isEnabledValue($value);
        self::assertSame($expected, $actual);
    }

    public function testGetConfiguration(): void
    {
        $values = [
            [
                'Variable_name' => 'Variable ON',
                'Value' => 'ON',
            ],
            [
                'Variable_name' => 'Variable empty',
                'Value' => '',
            ],
            [
                'Variable_name' => 'Variable other',
                'Value' => 'other',
            ],
        ];
        $manager = $this->createEntityManager($values);
        $service = new DatabaseInfoService($manager);
        $actual = $service->getConfiguration();
        self::assertCount(2, $actual);
    }

    public function testGetConfigurationWithException(): void
    {
        $manager = $this->createEntityManagerWithException();
        $service = new DatabaseInfoService($manager);
        $actual = $service->getConfiguration();
        self::assertSame([], $actual);
    }

    public function testGetDatabase(): void
    {
        $expected = [
            'Name' => 'database',
            'Version' => '10.11.15',
            'Host' => 'localhost',
            'Port' => '3008',
            'Driver' => 'pdo_mysql',
            'Charset' => 'utf8mb4',
        ];
        $manager = $this->createEntityManager();
        $service = new DatabaseInfoService($manager);
        $actual = $service->getDatabase();
        self::assertSame($expected, $actual);
    }

    public function testGetVersion(): void
    {
        $values = [
            [
                'Variable_name' => 'version',
                'Value' => '10.11.15-MariaDB-deb11-log',
            ],
        ];
        $expected = '10.11.15-MariaDB-deb11-log';
        $manager = $this->createEntityManager($values);
        $service = new DatabaseInfoService($manager);
        $actual = $service->getVersion();
        self::assertSame($expected, $actual);
    }

    public function testGetVersionWithException(): void
    {
        $manager = $this->createEntityManagerWithException();
        $service = new DatabaseInfoService($manager);
        $actual = $service->getVersion();
        self:assertSame('Unknown', $actual);
    }

    private function createEntityManager(array $values = []): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')
            ->willReturn(self::PARAMS);

        if ([] !== $values) {
            $result = $this->createMock(Result::class);
            $result->method('fetchAllAssociative')
                ->willReturn($values);

            $statement = $this->createMock(Statement::class);
            $statement->method('executeQuery')
                ->willReturn($result);

            $connection->method('prepare')
                ->willReturn($statement);
        }

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        return $manager;
    }

    private function createEntityManagerWithException(): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('prepare')
            ->willThrowException(new \Exception());

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        return $manager;
    }
}
