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

$oneHour = 3_600;
$oneDay = 86_400;
$oneMonth = 2_592_000;
$fifteenMinutes = 900;

$prefix = 'calculation.';
$adapter = 'cache.adapter.filesystem';

return App::config([
    'framework' => [
        'cache' => [
            'pools' => [
                // ApplicationService
                $prefix . 'application' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneHour,
                ],
                // UserService
                $prefix . 'user' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneHour,
                ],
                // SymfonyInfoService
                $prefix . 'symfony' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // ConstantExtension
                $prefix . 'constant' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // HelpService
                $prefix . 'help' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // CommandService
                $prefix . 'command' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // CacheService
                $prefix . 'cache' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // AssetVersionService
                $prefix . 'asset' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // ResponseListener
                $prefix . 'response' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // SearchService
                $prefix . 'search' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // LogService
                $prefix . 'log' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $fifteenMinutes,
                ],
                // SchemaService
                $prefix . 'schema' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneDay,
                ],
                // FontAwesomeImageService
                $prefix . 'fontawesome' => [
                    'adapter' => $adapter,
                    'default_lifetime' => $oneMonth,
                ],
            ],
        ],
    ],
]);
