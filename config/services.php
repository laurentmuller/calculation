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

use App\Service\LogParserService;
use Monolog\Formatter\LineFormatter;
use Twig\Extra\Markdown\MarkdownInterface;

$path = __DIR__ . '/../src/';

return App::config([
    'parameters' => [
        // application
        'app_name' => 'Calculation',
        'app_version' => '3.0.0',
        'app_name_full' => '%app_name% v%app_version%',
        'app_owner_name' => 'bibi.nu',
        'app_owner_url' => 'https://www.bibi.nu',
        'app_owner_city' => 'Montévraz',
        'app_description' => "Programme de calcul basé sur l'environnement de développement Symfony 7.x.",
        'app_secret' => '%env(string:APP_SECRET)%',
        'app_mode' => '%env(string:APP_MODE)%',
        // mailer
        'mailer_user_name' => '%app_name%',
        'mailer_user_email' => 'calculation@bibi.nu',
        // cookies
        'cookie_path' => '%env(string:COOKIE_PATH)%',
        // keys
        'akismet_key' => '%env(string:AKISMET_KEY)%',
        'ip_stack_key' => '%env(string:IP_STACK_KEY)%',
        'open_weather_key' => '%env(string:OPEN_WEATHER_KEY)%',
        'exchange_rate_key' => '%env(string:EXCHANGE_RATE_KEY)%',
        'bing_translator_key' => '%env(string:BING_TRANSLATOR_KEY)%',
        'google_translator_key' => '%env(string:GOOGLE_TRANSLATOR_KEY)%',
        'google_recaptcha_secret_key' => '%env(string:GOOGLE_RECAPTCHA_SECRET_KEY)%',
        'google_recaptcha_site_key' => '%env(string:GOOGLE_RECAPTCHA_SITE_KEY)%',
        'deepl_translator_key' => '%env(string:DEEPL_TRANSLATOR_KEY)%',
        // links
        'link_dev' => '%env(string:LINK_DEV)%',
        'link_prod' => '%env(string:LINK_PROD)%',
        // optimize
        '.container.dumper.inline_factories' => true,
        'debug.container.dump' => false,
    ],
    'services' => [
        '_defaults' => [
            'autowire' => true,
            'autoconfigure' => true,
            'bind' => [
                // Twig\Extra\Markdown\MarkdownInterface
                MarkdownInterface::class . ' $markdown' => '@twig.markdown.default',
            ],
        ],
        'App\\' => [
            'resource' => $path . '*/*',
            'exclude' => [
                $path . 'Kernel.php',
                $path . 'Calendar',
                $path . 'Entity',
                $path . 'Enums',
                $path . 'Faker',
                $path . 'Migrations',
                $path . 'Model',
                $path . 'Pdf',
                $path . 'Report',
                $path . 'Response',
                $path . 'Spreadsheet',
                $path . 'Traits',
                $path . 'Util',
                $path . 'Word',
            ],
        ],
        LogParserService::FORMATTER_NAME => [
            'class' => LineFormatter::class,
            'arguments' => [
                '$format' => "%%datetime%%|%%channel%%|%%level_name%%|%%extra.user%%|%%message%%|%%context%%\n",
                '$dateFormat' => LogParserService::DATE_FORMAT,
            ],
            'calls' => [
                ['setBasePath' => ['%kernel.project_dir%']],
            ],
        ],
    ],
]);
