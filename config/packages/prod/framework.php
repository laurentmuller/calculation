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
    $config->router()
        ->strictRequirements(null);

    $cache = $config->cache();

    $cache->pool('doctrine.metadata_cache_pool')
        ->adapters('cache.system');

    $cache->pool('doctrine.query_cache_pool')
        ->adapters('cache.system');

    $cache->pool('doctrine.result_cache_pool')
        ->adapters('cache.app');
};
