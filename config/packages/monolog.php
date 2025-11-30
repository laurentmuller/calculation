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

use App\Service\LogService;
use Psr\Log\LogLevel;

return App::config([
    'when@prod' => [
        'monolog' => [
            'channels' => ['deprecation'],
            'handlers' => [
                'main' => [
                    'type' => 'fingers_crossed',
                    'action_level' => LogLevel::ERROR,
                    'handler' => 'nested',
                    'buffer_size' => 50,
                    'excluded_http_codes' => [404, 405],
                ],
                'nested' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                    'level' => LogLevel::INFO,
                    'formatter' => LogService::FORMATTER_NAME,
                    'channels' => ['!deprecation'],
                ],
                'console' => [
                    'type' => 'console',
                    'process_psr_3_messages' => false,
                    'channels' => ['!event', '!doctrine', '!console', '!deprecation'],
                ],
                'deprecation' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.deprecations.log',
                    'formatter' => LogService::FORMATTER_NAME,
                    'channels' => ['deprecation'],
                ],
            ],
        ],
    ],
    'when@dev' => [
        'monolog' => [
            'channels' => ['deprecation'],
            'handlers' => [
                'main' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                    'level' => LogLevel::DEBUG,
                    'formatter' => LogService::FORMATTER_NAME,
                    'channels' => ['!event', '!deprecation'],
                ],
                'console' => [
                    'type' => 'console',
                    'process_psr_3_messages' => false,
                    'channels' => ['!event', '!doctrine', '!console', '!deprecation'],
                ],
                'deprecation' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.deprecations.log',
                    'formatter' => LogService::FORMATTER_NAME,
                    'channels' => ['deprecation'],
                ],
            ],
        ],
    ],
    'when@test' => [
        'monolog' => [
            'handlers' => [
                'main' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                    'formatter' => LogService::FORMATTER_NAME,
                    'channels' => ['app'],
                ],
            ],
        ],
    ],
]);
