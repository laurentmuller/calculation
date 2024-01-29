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

use Symfony\Config\DoctrineConfig;

return static function (DoctrineConfig $config): void {
    $config->orm()
        ->autoGenerateProxyClasses(false);

    $manager = $config->orm()
        ->entityManager('default');

    $manager->queryCacheDriver()
        ->type('pool')
        ->pool('doctrine.system_cache_pool');

    $manager->resultCacheDriver()
        ->type('pool')
        ->pool('doctrine.result_cache_pool');
};
