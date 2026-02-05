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

use App\Parameter\ApplicationParameters;
use App\Parameter\UserParameters;
use App\Service\IndexService;
use App\Utils\FormatUtils;

return App::config([
    'twig' => [
        'form_themes' => ['fields.html.twig'],
        'paths' => [
            '%kernel.project_dir%/public/css' => 'css',
            '%kernel.project_dir%/public/images' => 'images',
        ],
        'date' => [
            'format' => 'd.m.Y H:i:s',
            'interval_format' => '%%d jours',
            'timezone' => FormatUtils::DEFAULT_TIME_ZONE,
        ],
        'number_format' => [
            'decimals' => FormatUtils::FRACTION_DIGITS,
            'decimal_point' => FormatUtils::DECIMAL_SEP,
            'thousands_separator' => FormatUtils::THOUSANDS_SEP,
        ],
        'globals' => [
            'app_mode' => '%app_mode%',
            'app_name' => '%app_name%',
            'app_version' => '%app_version%',
            'app_name_full' => '%app_name_full%',
            'app_owner_name' => '%app_owner_name%',
            'app_owner_url' => '%app_owner_url%',
            'app_owner_city' => '%app_owner_city%',
            'app_description' => '%app_description%',

            'cookie_path' => '%cookie_path%',
            'mailer_user_email' => '%mailer_user_email%',
            'link_dev' => '%link_dev%',
            'link_prod' => '%link_prod%',
            'index_service' => '@' . IndexService::class,
            'user_params' => '@' . UserParameters::class,
            'app_params' => '@' . ApplicationParameters::class,
        ],
    ],
    'when@test' => [
        'twig' => [
            'strict_variables' => true,
        ],
    ],
]);
