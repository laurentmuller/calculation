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

namespace App\Tests\Fixture;

use App\Database\AbstractDatabase;
use App\Utils\FileUtils;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The database used for tests.
 */
class Database extends AbstractDatabase
{
    /**
     * Creates the database.
     */
    public static function createDatabase(): self
    {
        // remove the existing file
        self::deleteDatabase();

        // file
        $filename = self::getDatabaseFilename();

        // create
        return new self($filename);
    }

    /**
     * Make public for tests.
     */
    public function createIndex(string $table, string ...$columns): bool
    {
        return parent::createIndex($table, ...$columns);
    }

    /**
     * Delete the database.
     */
    public static function deleteDatabase(): ?self
    {
        $fs = new Filesystem();
        $filename = self::getDatabaseFilename();
        if ($fs->exists($filename)) {
            $fs->remove($filename);
        }

        return null;
    }

    /**
     * Make public for tests.
     *
     * @psalm-template T of array<string, mixed>
     *
     * @psalm-param int<1,3> $mode
     *
     * @psalm-return list<T>
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    public function executeAndFetch(\SQLite3Stmt $stmt, int $mode = \SQLITE3_ASSOC): array
    {
        /** @psalm-var list<T> */
        return parent::executeAndFetch($stmt, $mode);
    }

    /**
     * Gets the database file name.
     */
    public static function getDatabaseFilename(): string
    {
        return __DIR__ . '/../db_test.sqlite';
    }

    /**
     * Make public for tests.
     */
    public function getStatement(string $query): ?\SQLite3Stmt
    {
        return parent::getStatement($query);
    }

    /**
     * Make public for tests.
     */
    public function likeValue(string $value): string
    {
        return parent::likeValue($value);
    }

    /**
     * Make public for tests.
     *
     * @psalm-template T of array<string, mixed>
     *
     * @psalm-param int<1,3> $mode $mode
     *
     * @psalm-return array<int, T>
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    public function search(string $query, string $value, int $limit, int $mode = \SQLITE3_ASSOC): array
    {
        /** @psalm-var array<int, T> */
        return parent::search($query, $value, $limit, $mode);
    }

    protected function createSchema(): void
    {
        // load script
        $file = __DIR__ . '/../files/sql/db_test.sql';
        $sql = FileUtils::readFile($file);
        if ('' === $sql) {
            throw new \LogicException('Unable to find the schema.');
        }

        // execute
        if (!$this->exec($sql)) {
            throw new \LogicException('Unable to create the schema.');
        }
    }
}
