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
    $cache = $config->cache();

    // used by the ApplicationService
    $cache->pool('cache.service.application')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_hour);

    // used by the UserService
    $config->cache()->pool('cache.service.user')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_hour); // 1 hour

    // used by the SymfonyInfoService
    $config->cache()->pool('cache.service.symfony')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the ConstantExtension
    $config->cache()->pool('cache.service.constant')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);

    // used by the HelpService
    $config->cache()->pool('cache.service.help')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime($one_day);
};
