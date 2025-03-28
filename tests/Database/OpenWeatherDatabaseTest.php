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

namespace App\Tests\Database;

use App\Database\AbstractDatabase;
use App\Database\OpenWeatherDatabase;
use PHPUnit\Framework\TestCase;

class OpenWeatherDatabaseTest extends TestCase
{
    private const COUNTRY = 'USA';
    private const ID = 1;
    private const LATITUDE = -96.67;
    private const LONGITUDE = 107.87;
    private const NAME = 'New-York';

    private OpenWeatherDatabase $database;

    #[\Override]
    protected function setUp(): void
    {
        $this->database = new OpenWeatherDatabase(AbstractDatabase::IN_MEMORY);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->database->close();
    }

    public function testCount(): void
    {
        $actual = $this->database->count();
        self::assertSame(0, $actual);

        $actual = $this->insertCity();
        self::assertTrue($actual);

        $actual = $this->database->count();
        self::assertSame(1, $actual);
    }

    public function testDeleteCities(): void
    {
        $actual = $this->database->deleteCities();
        self::assertTrue($actual);
    }

    public function testFindByIdNotFound(): void
    {
        $actual = $this->database->findById(self::ID);
        self::assertFalse($actual);
    }

    public function testFindByIdSuccess(): void
    {
        $this->insertCity();
        $actual = $this->database->findById(self::ID);
        self::assertIsArray($actual);
    }

    public function testFindCity(): void
    {
        $this->insertCity();

        $actual = $this->database->findCity('fake');
        self::assertEmpty($actual);

        $actual = $this->database->findCity(self::NAME);
        self::assertCount(1, $actual);

        $row = $actual[0];
        self::assertSame(self::ID, $row['id']);
        self::assertSame(self::NAME, $row['name']);
        self::assertSame(self::COUNTRY, $row['country']);
        self::assertSame(self::LATITUDE, $row['latitude']);
        self::assertSame(self::LONGITUDE, $row['longitude']);
    }

    public function testFindCityCountry(): void
    {
        $this->insertCity();

        $actual = $this->database->findCityCountry('fake', 'fake');
        self::assertEmpty($actual);

        $actual = $this->database->findCityCountry(self::NAME, self::COUNTRY);
        self::assertCount(1, $actual);

        $row = $actual[0];
        self::assertSame(self::ID, $row['id']);
        self::assertSame(self::NAME, $row['name']);
        self::assertSame(self::COUNTRY, $row['country']);
        self::assertSame(self::LATITUDE, $row['latitude']);
        self::assertSame(self::LONGITUDE, $row['longitude']);
    }

    public function testFindCitySplit(): void
    {
        $actual = $this->database->findCity('fake, fake');
        self::assertEmpty($actual);
    }

    public function testFindWhenEmpty(): void
    {
        $actual = $this->database->findCity(self::NAME);
        self::assertEmpty($actual);

        $actual = $this->database->findCityCountry(self::NAME, self::COUNTRY);
        self::assertEmpty($actual);
    }

    private function insertCity(): bool
    {
        return $this->database->insertCity(
            self::ID,
            self::NAME,
            self::COUNTRY,
            self::LATITUDE,
            self::LONGITUDE
        );
    }
}
