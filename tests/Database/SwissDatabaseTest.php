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
use App\Database\SwissDatabase;
use PHPUnit\Framework\TestCase;

class SwissDatabaseTest extends TestCase
{
    private const CITY_ID = 1;
    private const CITY_NAME = 'New-York';
    private const CITY_ZIP = 3001;
    private const STATE_ABBREVIATION = 'CA';
    private const STATE_NAME = 'California';
    private const STREET_NAME = 'Palm Beach 2541';

    private SwissDatabase $database;

    #[\Override]
    protected function setUp(): void
    {
        $this->database = new SwissDatabase(AbstractDatabase::IN_MEMORY);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->database->close();
    }

    public function testFindCity(): void
    {
        $this->insertState();
        $this->insertCity();

        $actual = $this->database->findCity('fake');
        self::assertEmpty($actual);

        $actual = $this->database->findCity(self::CITY_NAME);
        self::assertCount(1, $actual);

        /** @phpstan-var array<string, mixed> $row */
        $row = $actual[0];
        self::assertSame(self::CITY_NAME, $row['name']);
        self::assertSame(self::CITY_ZIP, $row['zip']);
        self::assertSame(self::STATE_NAME, $row['state']);
    }

    public function testFindStreet(): void
    {
        $this->insertState();
        $this->insertCity();
        $this->insertStreet();

        $actual = $this->database->findStreet('fake');
        self::assertEmpty($actual);

        $actual = $this->database->findStreet(self::STREET_NAME);
        self::assertCount(1, $actual);

        /** @phpstan-var array<string, mixed> $row */
        $row = $actual[0];
        self::assertSame(self::STREET_NAME, $row['street']);
        self::assertSame(self::CITY_ZIP, $row['zip']);
        self::assertSame(self::CITY_NAME, $row['city']);
        self::assertSame(self::STATE_NAME, $row['state']);
    }

    public function testFindWhenEmpty(): void
    {
        $actual = $this->database->findAll(self::CITY_NAME);
        self::assertEmpty($actual);

        $actual = $this->database->findCity(self::CITY_NAME);
        self::assertEmpty($actual);

        $actual = $this->database->findZip((string) self::CITY_ZIP);
        self::assertEmpty($actual);

        $actual = $this->database->findStreet(self::STREET_NAME);
        self::assertEmpty($actual);
    }

    public function testFindZip(): void
    {
        $this->insertState();
        $this->insertCity();

        $actual = $this->database->findZip('fake');
        self::assertEmpty($actual);

        $actual = $this->database->findZip((string) self::CITY_ZIP);
        self::assertCount(1, $actual);

        /** @phpstan-var array<string, mixed> $row */
        $row = $actual[0];
        self::assertSame(self::CITY_ZIP, $row['zip']);
        self::assertSame(self::CITY_NAME, $row['city']);
        self::assertSame(self::STATE_NAME, $row['state']);
    }

    public function testInsertCity(): void
    {
        $actual = $this->insertCity();
        self::assertTrue($actual);
    }

    public function testInsertState(): void
    {
        $actual = $this->insertState();
        self::assertTrue($actual);
    }

    public function testInsertStreet(): void
    {
        $actual = $this->insertStreet();
        self::assertTrue($actual);
    }

    public function testTablesCount(): void
    {
        $actual = $this->database->getTablesCount();
        self::assertSame(0, $actual['state']);
        self::assertSame(0, $actual['city']);
        self::assertSame(0, $actual['street']);

        $this->insertState();
        $this->insertCity();
        $this->insertStreet();
        $actual = $this->database->getTablesCount();
        self::assertSame(1, $actual['state']);
        self::assertSame(1, $actual['city']);
        self::assertSame(1, $actual['street']);
    }

    /**
     * @phpstan-return array{0: int, 1: int, 2: string, 3:string}
     */
    private function getCity(): array
    {
        return [
            self::CITY_ID,
            self::CITY_ZIP,
            self::CITY_NAME,
            self::STATE_ABBREVIATION,
        ];
    }

    /**
     * @phpstan-return array{0: string, 1: string}
     */
    private function getState(): array
    {
        return [
            self::STATE_ABBREVIATION,
            self::STATE_NAME,
        ];
    }

    /**
     * @phpstan-return array{0: int, 1: string}
     */
    private function getStreet(): array
    {
        return [
            self::CITY_ID,
            self::STREET_NAME,
        ];
    }

    private function insertCity(): bool
    {
        return $this->database->insertCity($this->getCity());
    }

    private function insertState(): bool
    {
        return $this->database->insertState($this->getState());
    }

    private function insertStreet(): bool
    {
        return $this->database->insertStreet($this->getStreet());
    }
}
