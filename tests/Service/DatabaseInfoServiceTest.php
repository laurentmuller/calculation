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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
        ];
        $service = new DatabaseInfoService($this->createMockConnection('fetchAllAssociative', $values));
        $actual = $service->getConfiguration();
        self::assertCount(1, $actual);
    }

    public function testGetDatabase(): void
    {
        $expected = [
            'Name' => 'database',
            'Host' => 'localhost',
            'Port' => '3008',
            'Driver' => 'pdo_mysql',
            'Version' => '5.7.40',
            'Charset' => 'utf8mb4',
        ];
        $service = new DatabaseInfoService($this->createMockConnection('fetchAllAssociative'));
        $actual = $service->getDatabase();
        self::assertSame($expected, $actual);
    }

    public function testGetVersion(): void
    {
        $expected = '1.0.0';
        $values = ['Value' => $expected];
        $connection = $this->createMockConnection('fetchAssociative', $values);
        $service = new DatabaseInfoService($connection);
        $actual = $service->getVersion();
        self::assertSame($expected, $actual);
    }

    private function createMockConnection(string $method, array $values = []): MockObject&EntityManagerInterface
    {
        $result = $this->createMock(Result::class);
        $result->method($method)
            ->willReturn($values);

        $statement = $this->createMock(Statement::class);
        $statement->method('executeQuery')
            ->willReturn($result);

        $connection = $this->createMock(Connection::class);
        $connection->method('prepare')
            ->willReturn($statement);

        $connection->method('getParams')
            ->willReturn(self::PARAMS);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        return $manager;
    }
}
