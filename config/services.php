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

use App\Service\LogService;
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
        // links
        'link_dev' => '%env(string:LINK_DEV)%',
        'link_prod' => '%env(string:LINK_PROD)%',
        // optimize
        '.container.dumper.inline_factories' => true,
        'debug.container.dump' => false,
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
    $path = __DIR__ . '/../src/';
    $services->load('App\\', $path . '*')
        ->exclude([
            $path . 'Kernel.php',
            $path . 'Calendar',
            $path . 'Entity',
            $path . 'Enums',
            $path . 'Faker',
            $path . 'Migrations',
            $path . 'Model',
            $path . 'Pdf',
            $path . 'Report',
            $path . 'Spreadsheet',
            $path . 'Traits',
            $path . 'Util',
            $path . 'Word',
        ]);

    // custom line formatter
    $format = "%%datetime%%|%%channel%%|%%level_name%%|%%message%%|%%context%%|%%extra%%\n";
    $services->set(LogService::FORMATTER_NAME, LineFormatter::class)
        ->args([$format, LogService::DATE_FORMAT])
        ->call('setBasePath', ['%kernel.project_dir%']);
};
