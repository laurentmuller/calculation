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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertSame;

class DatabaseInfoServiceTest extends TestCase
{
    private const PARAMS = [
        'dbname' => 'database',
        'host' => 'localhost',
        'port' => 3008,
        'driver' => 'pdo_mysql',
        'serverVersion' => '5.7.40',
        'charset' => 'utf8mb4',
    ];

    /**
     * @phpstan-return \Generator<int, array{0: string, 1: bool}>
     */
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

    /**
     * @phpstan-return \Generator<int, array{0: string, 1: bool}>
     */
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
        $manager = $this->createMock(EntityManagerInterface::class);
        $service = new DatabaseInfoService($manager);
        $actual = $service->isDisabledValue($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getEnabledValues')]
    public function testEnabledValue(string $value, bool $expected): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
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
                'Variable_name' => 'empty',
                'Value' => '',
            ],
            [
                'Variable_name' => 'Variable Other',
                'Value' => 'other',
            ],
        ];
        $manager = $this->createEntityManager('fetchAllAssociative', $values);
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
            'Server' => 'MySql',
            'Version' => '5.7.40',
            'Name' => 'database',
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

    public function testGetDatabaseWithException(): void
    {
        $expected = [
            'Server' => 'MySql',
        ];

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willThrowException(new \Exception());

        $service = new DatabaseInfoService($manager);
        $actual = $service->getDatabase();
        self::assertSame($expected, $actual);
    }

    public function testGetVersion(): void
    {
        $expected = '1.0.0';
        $values = ['Value' => $expected];
        $manager = $this->createEntityManager('fetchAssociative', $values);
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

    private function createEntityManager(?string $method = null, array $values = []): MockObject&EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')
            ->willReturn(self::PARAMS);

        if (null !== $method) {
            $result = $this->createMock(Result::class);
            $result->method($method)
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

    private function createEntityManagerWithException(): MockObject&EntityManagerInterface
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
