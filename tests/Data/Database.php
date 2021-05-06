<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Data;

use App\Database\AbstractDatabase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The database test.
 *
 * @author Laurent Muller
 */
class Database extends AbstractDatabase
{
    /**
     * Creates the database.
     */
    public static function createDatabase(): self
    {
        // remove existing file
        self::deleteDatabase();

        // file
        $filename = self::getDatabaseFilename();

        // create
        return new self($filename);
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
     * Gets the database file name.
     */
    public static function getDatabaseFilename(): string
    {
        return __DIR__ . '/db_test.sqlite';
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(): void
    {
        // load script
        $file = __DIR__ . '/db_test.sql';
        $sql = (string)\file_get_contents($file);

        // execute
        $this->exec($sql);
    }
}
