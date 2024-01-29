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

use App\Service\UserService;
use Symfony\Config\TwigConfig;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (TwigConfig $config): void {
    $config->formThemes(['fields.html.twig'])
        ->path('%kernel.project_dir%/public/css', 'css')
        ->path('%kernel.project_dir%/public/images', 'images');

    $config->date()
        ->format('d.m.Y H:i:s')
        ->intervalFormat('%%d jours');

    $config->numberFormat()
        ->decimals(2)
        ->decimalPoint('.')
        ->thousandsSeparator('\'');

    $globals = [
        'app_name' => '%app_name%',
        'app_version' => '%app_version%',
        'app_name_version' => '%app_name_version%',
        'app_owner_name' => '%app_owner_name%',
        'app_owner_url' => '%app_owner_url%',
        'app_owner_city' => '%app_owner_city%',
        'app_description' => '%app_description%',
        'app_mode' => '%app_mode%',
        'cookie_path' => '%cookie_path%',
        'mailer_user_email' => '%mailer_user_email%',
        'link_dev' => '%link_dev%',
        'link_prod' => '%link_prod%',
        'user_service' => service(UserService::class),
    ];
    foreach ($globals as $key => $value) {
        $config->global($key, $value);
    }
};
