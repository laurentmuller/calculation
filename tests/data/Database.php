<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\data;

use App\Database\AbstractDatabase;

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
        $filename = __DIR__ . '/db_test.sqlite';
        if (\file_exists($filename)) {
            \unlink($filename);
        }

        // create
        return new self($filename);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(): void
    {
        // load script
        $file = __DIR__ . '/db_test.sql';
        $sql = \file_get_contents($file);

        // execute
        $this->exec($sql);
    }
}
