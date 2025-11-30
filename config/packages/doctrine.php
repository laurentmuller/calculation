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

use App\Types\FixedFloatType;

return App::config([
    'doctrine' => [
        'dbal' => [
            'connections' => [
                'default' => [
                    'url' => '%env(resolve:DATABASE_URL)%',
                    'profiling_collect_backtrace' => '%kernel.debug%',
                ],
            ],
            'types' => [FixedFloatType::NAME => FixedFloatType::class],
        ],
        'orm' => [
            'auto_generate_proxy_classes' => true,
            'proxy_dir' => '%kernel.cache_dir%/doctrine/orm/Proxies',
            'controller_resolver' => [
                'auto_mapping' => false,
            ],
            'entity_managers' => [
                'default' => [
                    'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                    'mappings' => [
                        'App' => [
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/src/Entity',
                            'prefix' => 'App\Entity',
                            'alias' => 'App',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'when@prod' => [
        'doctrine' => [
            'orm' => [
                'auto_generate_proxy_classes' => false,
                'entity_managers' => [
                    'default' => [
                        'metadata_cache_driver' => [
                            'type' => 'pool',
                            'pool' => 'doctrine.metadata',
                        ],
                        'query_cache_driver' => [
                            'type' => 'pool',
                            'pool' => 'doctrine.query',
                        ],
                        'result_cache_driver' => [
                            'type' => 'pool',
                            'pool' => 'doctrine.result',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'when@test' => [
        'doctrine' => [
            'dbal' => [
                'connections' => [
                    'default' => [
                        'logging' => false,
                    ],
                ],
            ],
        ],
    ],
]);
