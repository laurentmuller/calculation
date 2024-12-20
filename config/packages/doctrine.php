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

use App\Types\FixedFloatType;
use Symfony\Config\DoctrineConfig;

return static function (DoctrineConfig $config): void {
    $dbal = $config->dbal();
    $dbal->connection('default')
        ->url('%env(resolve:DATABASE_URL)%')
        ->profilingCollectBacktrace('%kernel.debug%');

    $dbal->type(FixedFloatType::NAME)
        ->class(FixedFloatType::class);

    $orm = $config->orm();
    $orm->autoGenerateProxyClasses(true)
        ->proxyDir('%kernel.cache_dir%/doctrine/orm/Proxies');

    $orm->controllerResolver()
        ->autoMapping(false);

    $manager = $orm->entityManager('default');
    $manager->autoMapping(true)
        ->validateXmlMapping(true)
        ->reportFieldsWhereDeclared(true)
        ->namingStrategy('doctrine.orm.naming_strategy.underscore_number_aware');

    $manager->mapping('App')
        ->alias('App')
        ->prefix('App\Entity')
        ->dir('%kernel.project_dir%/src/Entity');
};
