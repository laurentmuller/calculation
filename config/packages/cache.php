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
                // Application and User Parameters
                CacheAttributes::CACHE_PARAMETERS => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_HOUR,
                    'tags' => true,
                ],
                // SymfonyInfoService
                CacheAttributes::CACHE_SYMFONY => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // ConstantExtension
                CacheAttributes::CACHE_CONSTANT => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // HelpService
                CacheAttributes::CACHE_HELP => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // CommandService
                CacheAttributes::CACHE_COMMAND => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // CacheService
                CacheAttributes::CACHE_SERVICE => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // AssetVersionService
                CacheAttributes::CACHE_ASSET => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // ResponseListener
                CacheAttributes::CACHE_RESPONSE => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // SearchService
                CacheAttributes::CACHE_SEARCH => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // LogService
                CacheAttributes::CACHE_LOG => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_FIFTEEN_MINUTES,
                ],
                // SchemaService
                CacheAttributes::CACHE_SCHEMA => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_DAY,
                ],
                // FontAwesomeImageService
                CacheAttributes::CACHE_FONT_AWESOME => [
                    'adapter' => CacheAttributes::CACHE_ADAPTER,
                    'default_lifetime' => CacheAttributes::LIFE_TIME_ONE_MONTH,
                ],
            ],
        ],
    ],
]);
