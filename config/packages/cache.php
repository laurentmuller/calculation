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
    $cache = $config->cache();

    // used by the ApplicationService
    $cache->pool('cache.app.service')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime(3600); // 1 hour

    // used by the UserService
    $config->cache()->pool('cache.user.service')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime(3600); // 1 hour

    // used by the SymfonyInfoService
    $config->cache()->pool('cache.symfony.service')
        ->adapters('cache.adapter.filesystem')
        ->defaultLifetime(86_400); // 1 day
};
