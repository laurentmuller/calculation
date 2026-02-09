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

use App\Constants\CacheAttributes;

return App::config([
    'framework' => [
        'cache' => [
            'pools' => [
                // ApplicationParameters
                CacheAttributes::CACHE_PARAMETERS => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_HOUR,
                ],
                // UserParameters
                CacheAttributes::CACHE_USER => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_HOUR,
                ],
                // SymfonyInfoService
                CacheAttributes::CACHE_SYMFONY => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // ConstantExtension
                CacheAttributes::CACHE_CONSTANT => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // HelpService
                CacheAttributes::CACHE_HELP => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // CommandService
                CacheAttributes::CACHE_COMMAND => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // CacheService
                CacheAttributes::CACHE_SERVICE => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // AssetVersionService
                CacheAttributes::CACHE_ASSET => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // ResponseListener
                CacheAttributes::CACHE_RESPONSE => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // SearchService
                CacheAttributes::CACHE_SEARCH => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // LogService
                CacheAttributes::CACHE_LOG => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_FIFTEEN_MINUTES,
                ],
                // SchemaService
                CacheAttributes::CACHE_SCHEMA => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // FontAwesomeImageService
                CacheAttributes::CACHE_FONT_AWESOME => [
                    'adapter' => 'cache.adapter.filesystem',
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_MONTH,
                ],
            ],
        ],
    ],
]);
