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

use Symfony\Component\Dotenv\Dotenv;

require \dirname(__DIR__) . '/vendor/autoload.php';

if (\file_exists(\dirname(__DIR__) . '/config/bootstrap.php')) {
    require \dirname(__DIR__) . '/config/bootstrap.php';
} elseif (\method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(\dirname(__DIR__) . '/.env');
}
