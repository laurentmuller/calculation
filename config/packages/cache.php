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
    $one_hour = 3_600;
    $one_day = 86_400;
    $fiveteen_minutes = 900;

    $cache = $config->cache();

    // used by the ApplicationService
    $cache->pool('calculation.service.application')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_hour);

    // used by the UserService
    $config->cache()->pool('calculation.service.user')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_hour);

    // used by the SymfonyInfoService
    $config->cache()->pool('calculation.service.symfony')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the ConstantExtension
    $config->cache()->pool('calculation.service.constant')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the HelpService
    $config->cache()->pool('calculation.service.help')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the CommandService
    $config->cache()->pool('calculation.service.command')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the CacheService
    $config->cache()->pool('calculation.service.cache')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the AssetVersionService
    $config->cache()->pool('calculation.service.asset')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the ResponseListener
    $config->cache()->pool('calculation.service.response')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the SearchService
    $config->cache()->pool('calculation.service.search')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the LogService
    $config->cache()->pool('calculation.service.log')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($fiveteen_minutes);

    // used by the SchemaService
    $config->cache()->pool('calculation.service.schema')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);
};
