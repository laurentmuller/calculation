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

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->test(true);

    $config->profiler()
        ->collect(false);

    $config->session()
        ->storageFactoryId('session.storage.factory.mock_file');

    $config->validation()
        ->notCompromisedPassword()
        ->enabled(false);
};
