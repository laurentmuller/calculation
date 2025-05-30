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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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
    #[\Override]
    public function createIndex(string $table, string ...$columns): bool
    {
        return parent::createIndex($table, ...$columns);
    }

    /**
     * Delete the database.
     */
    public static function deleteDatabase(): ?self
    {
        $count = 0;
        $fs = new Filesystem();
        $filename = self::getDatabaseFilename();
        while ($fs->exists($filename) && ++$count < 5) {
            try {
                $fs->remove($filename);
            } catch (IOException) {
                \sleep(1);
            }
        }

        return null;
    }

    /**
     * Make public for tests.
     *
     * @template T of array<string, mixed>
     *
     * @param int<1,3> $mode
     *
     * @return array<int, T>
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    #[\Override]
    public function executeAndFetch(\SQLite3Stmt $stmt, int $mode = \SQLITE3_ASSOC): array
    {
        /** @var array<int, T> */
        return parent::executeAndFetch($stmt, $mode);
    }

    /**
     * Gets the database file name.
     */
    public static function getDatabaseFilename(): string
    {
        static $fileName = null;
        if (null === $fileName) {
            $fileName = Path::normalize(__DIR__ . '/db_test.sqlite');
        }

        /** @phpstan-var string */
        return $fileName;
    }

    /**
     * Make public for tests.
     */
    #[\Override]
    public function getStatement(string $query): ?\SQLite3Stmt
    {
        return parent::getStatement($query);
    }

    /**
     * Make public for tests.
     */
    #[\Override]
    public function likeValue(string $value): string
    {
        return parent::likeValue($value);
    }

    /**
     * Make public for tests.
     *
     * @template T of array<string, mixed>
     *
     * @param int<1,3> $mode
     *
     * @return array<int, T>
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    #[\Override]
    public function search(string $query, string $value, int $limit, int $mode = \SQLITE3_ASSOC): array
    {
        /** @var array<int, T> */
        return parent::search($query, $value, $limit, $mode);
    }

    #[\Override]
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
