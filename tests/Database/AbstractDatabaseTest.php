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
use App\Tests\Data\Database;
use PHPUnit\Framework\TestCase;

class AbstractDatabaseTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        $this->database = new Database(AbstractDatabase::IN_MEMORY);
    }

    protected function tearDown(): void
    {
        $this->database->close();
    }

    public function testCloseRollback(): void
    {
        $database = new Database(AbstractDatabase::IN_MEMORY);
        $database->beginTransaction();
        $database->close();
        self::assertFalse($database->isTransaction());
    }

    public function testCompact(): void
    {
        $actual = $this->database->compact();
        self::assertTrue($actual);
    }

    public function testConstructorWithFile(): void
    {
        $filename = __DIR__ . '/test.db';

        try {
            $database = new Database($filename);
            self::assertFalse($database->isTransaction());
            $database->close();

            $database = new Database($filename);
            self::assertFalse($database->isTransaction());
            $database->close();

            $database = new Database($filename, true);
            self::assertFalse($database->isTransaction());
            $database->close();
        } finally {
            \unlink($filename);
        }
    }

    public function testCreateInvalidIndex(): void
    {
        $actual = $this->database->createIndex('sy_Calculation', 'fake');
        self::assertFalse($actual);
    }

    public function testCreateValidIndex(): void
    {
        $actual = $this->database->createIndex('sy_Calculation', 'customer');
        self::assertTrue($actual);
    }

    public function testExecuteAndFetch(): void
    {
        $query = 'SELECT * FROM sy_User WHERE sy_User.username LIKE :value LIMIT :limit';
        $stmt = $this->database->prepare($query);
        self::assertInstanceOf(\SQLite3Stmt::class, $stmt);
        $actual = $this->database->executeAndFetch($stmt);
        self::assertCount(0, $actual);
    }

    public function testInvalidStatement(): void
    {
        $query = 'SELECT * FROM sy_NotFound';
        $stmt = $this->database->getStatement($query);
        self::assertNull($stmt);
    }

    public function testLikeValue(): void
    {
        $expected = '%value%';
        $actual = $this->database->likeValue('value');
        self::assertSame($expected, $actual);
        $actual = $this->database->likeValue(' value ');
        self::assertSame($expected, $actual);
    }

    public function testPragma(): void
    {
        $actual = $this->database->pragma('auto_vacuum');
        self::assertTrue($actual);

        $actual = $this->database->pragma('auto_vacuum', 1);
        self::assertTrue($actual);
    }

    public function testRecordsCount(): void
    {
        $expected = 0;
        $actual = $this->database->getRecordsCount('sy_Calculation');
        self::assertSame($expected, $actual);
    }

    public function testSearch(): void
    {
        $query = 'SELECT * FROM sy_User WHERE sy_User.username LIKE :value LIMIT :limit';

        $actual = $this->database->search($query, 'fake value', 10);
        self::assertCount(0, $actual);

        $actual = $this->database->search($query, 'ROLE_ADMIN', 10);
        self::assertCount(1, $actual);
    }

    public function testToString(): void
    {
        $actual = $this->database->getFilename();
        self::assertSame(AbstractDatabase::IN_MEMORY, $actual);

        $actual = $this->database->__toString();
        self::assertSame(AbstractDatabase::IN_MEMORY, $actual);
    }

    public function testTransaction(): void
    {
        self::assertFalse($this->database->isTransaction());
        $actual = $this->database->rollbackTransaction();
        self::assertFalse($actual);

        $actual = $this->database->beginTransaction();
        self::assertTrue($actual);
        self::assertTrue($this->database->isTransaction());

        $actual = $this->database->beginTransaction();
        self::assertFalse($actual);

        $actual = $this->database->commitTransaction();
        self::assertTrue($actual);
        self::assertFalse($this->database->isTransaction());

        $actual = $this->database->commitTransaction();
        self::assertFalse($actual);
    }

    public function testValidStatement(): void
    {
        $query = 'SELECT * FROM sy_User';

        // first
        $stmt = $this->database->getStatement($query);
        self::assertInstanceOf(\SQLite3Stmt::class, $stmt);

        // second to testing cache
        $stmt = $this->database->getStatement($query);
        self::assertInstanceOf(\SQLite3Stmt::class, $stmt);
    }
}
