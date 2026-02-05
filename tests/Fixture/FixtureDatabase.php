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

/**
 * The database for tests with public methods.
 */
class FixtureDatabase extends AbstractDatabase
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
     * @return array<array<string, mixed>>
     */
    #[\Override]
    public function executeAndFetch(\SQLite3Stmt $stmt): array
    {
        return parent::executeAndFetch($stmt);
    }

    /**
     * Gets the database file name.
     */
    public static function getDatabaseFilename(): string
    {
        static $fileName = null;
        if (null === $fileName) {
            $fileName = FileUtils::normalize(__DIR__ . '/db_test.sqlite');
        }

        return $fileName;
    }

    #[\Override]
    public function getStatement(string $query): ?\SQLite3Stmt
    {
        return parent::getStatement($query);
    }

    #[\Override]
    public function likeValue(string $value): string
    {
        return parent::likeValue($value);
    }

    /**
     * @return array<array<string, mixed>>
     */
    #[\Override]
    public function search(string $query, string $value, int $limit): array
    {
        return parent::search($query, $value, $limit);
    }

    #[\Override]
    protected function createSchema(): void
    {
        $file = __DIR__ . '/../files/sql/db_test.sql';
        $sql = FileUtils::readFile($file);
        if (null === $sql) {
            throw new \LogicException('Unable to find the schema.');
        }

        if (!$this->exec($sql)) {
            throw new \LogicException('Unable to create the schema.');
        }
    }
}
