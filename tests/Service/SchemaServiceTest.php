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

use App\Service\SchemaService;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;

class SchemaServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private SchemaService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(SchemaService::class);
    }

    public function testCountAllWithoutRows(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        $cache = new NullAdapter();
        $service = new SchemaService($manager, $cache);
        $tables = $service->getTables();
        self::assertCount(0, $tables);
    }

    public function testCountAllWithRows(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());
        $connection->method('getDatabase')
            ->willReturn('fake');
        $row = [
            'name' => 'sy_Group',
            'records' => 1,
            'size' => 1,
        ];
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')
            ->willReturn([$row]);

        $connection->method('executeQuery')
            ->willReturn($result);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        $cache = new NullAdapter();
        $service = new SchemaService($manager, $cache);
        $tables = $service->getTables();
        self::assertCount(0, $tables);
    }

    public function testGetTable(): void
    {
        $name = 'sy_group';
        $actual = $this->service->getTable($name);
        self::assertSame($name, $actual['name']);
    }

    public function testGetTables(): void
    {
        $actual = $this->service->getTables();
        self::assertNotEmpty($actual);
    }

    public function testMySQLPlatformThrowException(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
            ->willThrowException(new ConnectionException());
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        $cache = new NullAdapter();
        $service = new SchemaService($manager, $cache);
        $tables = $service->getTables();
        self::assertCount(0, $tables);
    }

    public function testTableExists(): void
    {
        $actual = $this->service->tableExists('sy_Group');
        self::assertTrue($actual);
        $actual = $this->service->tableExists('fake');
        self::assertFalse($actual);
    }
}
