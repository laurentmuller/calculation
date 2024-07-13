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

    public function testPragma(): void
    {
        $actual = $this->database->pragma('auto_vacuum');
        self::assertTrue($actual);

        $actual = $this->database->pragma('auto_vacuum', 1);
        self::assertTrue($actual);
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
}
