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
use Symfony\Component\Filesystem\Filesystem;

$logfile = \dirname(__DIR__) . '/var/log/test.log';

if (\file_exists($logfile)) {
    \file_put_contents($logfile, '');
}

require \dirname(__DIR__) . '/vendor/autoload.php';

// clear cache
try {
    $fs = new Filesystem();
    $fs->remove([__DIR__ . '/../var/cache/test']);
} catch (\Exception) {
    // ignore
}

$file = \dirname(__DIR__) . '/config/bootstrap.php';
if (\file_exists($file)) {
    require $file;
} elseif (\method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(\dirname(__DIR__) . '/.env');
}
