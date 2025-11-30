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

use DoctrineExtensions\Query\Mysql as MySqlFunction;
use DoctrineExtensions\Query\Sqlite as SqliteFunction;

return App::config([
    'doctrine' => [
        'orm' => [
            'entity_managers' => [
                'default' => [
                    'dql' => [
                        'datetime_functions' => [
                            'date_format' => MySqlFunction\DateFormat::class,
                            'day' => MySqlFunction\Day::class,
                            'month' => MySqlFunction\Month::class,
                            'week' => MySqlFunction\Week::class,
                            'year' => MySqlFunction\Year::class,
                        ],
                        'numeric_functions' => [
                            'round' => MySqlFunction\Round::class,
                        ],
                        'string_functions' => [
                            'ifelse' => MySqlFunction\IfElse::class,
                            'ifnull' => MySqlFunction\IfNull::class,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'when@test' => [
        'doctrine' => [
            'orm' => [
                'entity_managers' => [
                    'default' => [
                        'dql' => [
                            'datetime_functions' => [
                                'date_format' => SqliteFunction\DateFormat::class,
                                'day' => SqliteFunction\Day::class,
                                'month' => SqliteFunction\Month::class,
                                'week' => SqliteFunction\Week::class,
                                'year' => SqliteFunction\Year::class,
                            ],
                            'numeric_functions' => [
                                'round' => SqliteFunction\Round::class,
                            ],
                            'string_functions' => [
                                'ifelse' => SqliteFunction\IfElse::class,
                                'ifnull' => SqliteFunction\IfNull::class,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
