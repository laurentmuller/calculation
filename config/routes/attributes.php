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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Routing\Loader\Configurator\Routes;

return Routes::config([
    'controllers' => [
        'resource' => 'routing.controllers',
    ],
    'when@dev' => [
        ' _error' => [
            'resource' => '@FrameworkBundle/Resources/config/routing/errors.php',
            'prefix' => '/_error',
        ],
        'web_profiler_wdt' => [
            'resource' => '@WebProfilerBundle/Resources/config/routing/wdt.php',
            'prefix' => '/_wdt',
        ],
        'web_profiler_profiler' => [
            'resource' => '@WebProfilerBundle/Resources/config/routing/profiler.php',
            'prefix' => '/_profiler',
        ],
    ],
]);
