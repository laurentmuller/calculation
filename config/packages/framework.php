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

use App\Service\AssetVersionService;
use App\Utils\FormatUtils;

return App::config([
    'framework' => [
        'secret' => '%env(APP_SECRET)%',
        'http_method_override' => true,
        'default_locale' => FormatUtils::DEFAULT_LOCALE,
        'enabled_locales' => [FormatUtils::DEFAULT_LOCALE],
        'session' => [
            'name' => 'CALCULATION_SESSION_ID',
            'cookie_path' => '%cookie_path%',
            'enabled' => true,
        ],
        'mailer' => [
            'dsn' => '%env(MAILER_DSN)%',
            'envelope' => [
                'sender' => '%mailer_user_email%',
            ],
        ],
        'assets' => [
            'version_strategy' => AssetVersionService::class,
            'strict_mode' => '%kernel.debug%',
        ],
        'router' => [
            'utf8' => true,
        ],
        'php_errors' => [
            'log' => true,
        ],
        'form' => [
            'csrf_protection' => [
                'field_name' => 'csrf_token',
            ],
        ],
        'property_info' => [
            'with_constructor_extractor' => true,
        ],
    ],
    'when@dev' => [
        'framework' => [
            'ide' => 'phpstorm',
            'profiler' => [
                'only_exceptions' => false,
                'collect_serializer_data' => true,
            ],
        ],
    ],
    'when@prod' => [
        'framework' => [
            'router' => [
                'strict_requirements' => null,
            ],
            'cache' => [
                'pools' => [
                    'doctrine.metadata' => [
                        'adapter' => 'cache.system',
                    ],
                    'doctrine.query' => [
                        'adapter' => 'cache.system',
                    ],
                    'doctrine.result' => [
                        'adapter' => 'cache.app',
                    ],
                ],
            ],
        ],
    ],
    'when@test' => [
        'framework' => [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
            'validation' => [
                'not_compromised_password' => false,
            ],
        ],
    ],
]);
