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

namespace App\Tests;

use Symfony\Component\Dotenv\Dotenv;

$logfile = \dirname(__DIR__) . '/var/log/test.log';

// @phpstan-ignore-next-line
if (\file_exists($logfile)) {
    // @phpstan-ignore-next-line
    \file_put_contents($logfile, '');
}

require \dirname(__DIR__) . '/vendor/autoload.php';

// @phpstan-ignore-next-line
if (\file_exists(\dirname(__DIR__) . '/config/bootstrap.php')) {
    require \dirname(__DIR__) . '/config/bootstrap.php';
} elseif (\method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(\dirname(__DIR__) . '/.env');
}
