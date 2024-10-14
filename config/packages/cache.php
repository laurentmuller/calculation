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
    $fifteen_minutes = 900;
    $one_hour = 3_600;
    $one_day = 86_400;

    $cache = $config->cache();

    // ApplicationService
    $cache->pool('calculation.application')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_hour);

    // UserService
    $config->cache()->pool('calculation.user')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_hour);

    // SymfonyInfoService
    $config->cache()->pool('calculation.symfony')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // ConstantExtension
    $config->cache()->pool('calculation.constant')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // HelpService
    $config->cache()->pool('calculation.help')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // CommandService
    $config->cache()->pool('calculation.command')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // CacheService
    $config->cache()->pool('calculation.cache')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // AssetVersionService
    $config->cache()->pool('calculation.asset')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // ResponseListener
    $config->cache()->pool('calculation.response')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // SearchService
    $config->cache()->pool('calculation.search')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // LogService
    $config->cache()->pool('calculation.log')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($fifteen_minutes);

    // SchemaService
    $config->cache()->pool('calculation.schema')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // FontAwesomeService
    $config->cache()->pool('calculation.fontawesome')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);
};
