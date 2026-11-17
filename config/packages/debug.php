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

return App::config([
    'when@dev' => [
        'debug' => [
            'dump_destination' => 'tcp://%env(VAR_DUMPER_SERVER)%',
        ],
        'web_profiler' => [
            'toolbar' => true,
            'excluded_ajax_paths' => '^/((index|app(_[\w]+)?)\.php/)?_wdt|\.well-known/appspecific/com\.chrome\.devtools\.json',
        ],
    ],
    'when@test' => [
        'web_profiler' => [
            'toolbar' => false,
        ],
    ],
]);
