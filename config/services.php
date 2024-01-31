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

use Monolog\Formatter\LineFormatter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $config): void {
    // parameters
    $values = [
        // fixed
        'app_name' => 'Calculation',
        'app_version' => '3.0.0',
        'app_owner_name' => 'bibi.nu',
        'app_owner_url' => 'https://www.bibi.nu',
        'app_owner_city' => 'Montévraz',
        'app_description' => "Programme de calcul basé sur l'environnement de développement Symfony 7.x.",
        'app_name_version' => '%app_name% v%app_version%',
        'app_secret' => '%env(string:APP_SECRET)%',
        'app_mode' => '%env(string:APP_MODE)%',
        // mailer
        'mailer_user_name' => '%app_name%',
        'mailer_user_email' => 'calculation@bibi.nu',
        // cookies
        'cookie_path' => '%env(string:string:COOKIE_PATH)%',
        // keys
        'akismet_key' => '%env(string:AKISMET_KEY)%',
        'ip_stack_key' => '%env(string:IP_STACK_KEY)%',
        'open_weather_key' => '%env(string:OPEN_WEATHER_KEY)%',
        'exchange_rate_key' => '%env(string:EXCHANGE_RATE_KEY)%',
        'bing_translator_key' => '%env(string:BING_TRANSLATOR_KEY)%',
        'google_translator_key' => '%env(string:GOOGLE_TRANSLATOR_KEY)%',
        'google_recaptcha_secret_key' => '%env(string:GOOGLE_RECAPTCHA_SECRET_KEY)%',
        'google_recaptcha_site_key' => '%env(string:GOOGLE_RECAPTCHA_SITE_KEY)%',
        // links
        'link_dev' => '%env(string:LINK_DEV)%',
        'link_prod' => '%env(string:LINK_PROD)%',
        // optimize
        '.container.dumper.inline_factories' => true,
        'debug.container.dump' => false,
        // the date format for log entries
        'log_date_format' => 'd.m.Y H:i:s.v',
    ];
    $parameters = $config->parameters();
    foreach ($values as $key => $value) {
        $parameters->set($key, $value);
    }

    // default configuration for services
    $services = $config->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('Twig\Extra\Markdown\MarkdownInterface $markdown', service('twig.markdown.default'));

    // make classes in src available to be used as services
    $services->load('App\\', __DIR__ . '/../src/*')
        ->exclude([
            __DIR__ . '/../src/Kernel.php',
            __DIR__ . '/../src/Calendar',
            __DIR__ . '/../src/Entity',
            __DIR__ . '/../src/Enums',
            __DIR__ . '/../src/Faker',
            __DIR__ . '/../src/Migrations',
            __DIR__ . '/../src/Model',
            __DIR__ . '/../src/Pdf',
            __DIR__ . '/../src/Report',
            __DIR__ . '/../src/Spreadsheet',
            __DIR__ . '/../src/Traits',
            __DIR__ . '/../src/Util',
            __DIR__ . '/../src/Word',
        ]);

    // custom line and date formatter for monolog
    $services->set('monolog.custom_formatter', LineFormatter::class)
        ->args(["%%datetime%%|%%channel%%|%%level_name%%|%%message%%|%%context%%|%%extra%%\n", '%log_date_format%']);
};
