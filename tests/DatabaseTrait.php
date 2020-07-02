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

namespace App\Tests;

use App\Tests\data\Database;

/**
 * Trait to manage database test.
 *
 * @author Laurent Muller
 */
trait DatabaseTrait
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        Database::createDatabase();
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        Database::deleteDatabase();
    }
}
