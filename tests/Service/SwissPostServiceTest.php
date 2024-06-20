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

use App\Service\SwissPostService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SwissPostService::class)]
class SwissPostServiceTest extends TestCase
{
    private string $databaseName;
    private SwissPostService $service;

    protected function setUp(): void
    {
        $this->databaseName = __DIR__ . '/../Data/swiss_test.sqlite';
        $this->service = new SwissPostService($this->databaseName);
    }

    public function testCount(): void
    {
        $actual = $this->service
            ->getTablesCount();
        self::assertCount(3, $actual);
        self::assertArrayHasKey('state', $actual);
        self::assertArrayHasKey('city', $actual);
        self::assertArrayHasKey('street', $actual);
        self::assertSame(1, $actual['state']);
        self::assertSame(1, $actual['city']);
        self::assertSame(55, $actual['street']);
    }

    public function testFindAllFound(): void
    {
        $actual = $this->service
            ->findAll('1753');
        self::assertCount(25, $actual);
        self::assertIsArray($actual[0]);

        $actual = $actual[0];
        self::assertArrayHasKey('street', $actual);
        self::assertArrayHasKey('zip', $actual);
        self::assertArrayHasKey('city', $actual);
        self::assertArrayHasKey('state', $actual);
        self::assertArrayHasKey('display', $actual);
    }

    public function testFindAllNotFound(): void
    {
        $actual = $this->service
            ->findAll('fake');
        self::assertCount(0, $actual);
    }

    public function testFindCityFound(): void
    {
        $actual = $this->service
            ->findCity('matran');
        self::assertCount(1, $actual);
        self::assertIsArray($actual[0]);

        $actual = $actual[0];
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('zip', $actual);
        self::assertArrayHasKey('state', $actual);
        self::assertArrayHasKey('display', $actual);
    }

    public function testFindCityNotFound(): void
    {
        $actual = $this->service
            ->findCity('fake');
        self::assertCount(0, $actual);
    }

    public function testFindStreetFound(): void
    {
        $actual = $this->service
            ->findStreet('route du bois');
        self::assertCount(1, $actual);
        self::assertIsArray($actual[0]);

        $actual = $actual[0];
        self::assertArrayHasKey('street', $actual);
        self::assertArrayHasKey('zip', $actual);
        self::assertArrayHasKey('city', $actual);
        self::assertArrayHasKey('state', $actual);
        self::assertArrayHasKey('display', $actual);
    }

    public function testFindStreetNotFound(): void
    {
        $actual = $this->service
            ->findStreet('fake');
        self::assertCount(0, $actual);
    }

    public function testFindZipFound(): void
    {
        $actual = $this->service
            ->findZip('1753');
        self::assertCount(1, $actual);
        self::assertIsArray($actual[0]);

        $actual = $actual[0];
        self::assertArrayHasKey('zip', $actual);
        self::assertArrayHasKey('city', $actual);
        self::assertArrayHasKey('state', $actual);
        self::assertArrayHasKey('display', $actual);
    }

    public function testFindZipNotFound(): void
    {
        $actual = $this->service
            ->findZip('fake');
        self::assertCount(0, $actual);
    }

    public function testGetDatabaseName(): void
    {
        $actual = $this->service
            ->getDatabaseName();
        self::assertSame($this->databaseName, $actual);
    }
}
