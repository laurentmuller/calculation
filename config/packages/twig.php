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
use App\Service\ApplicationService;
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
            // application
            'app_mode' => '%app_mode%',
            'app_name' => ApplicationService::APP_NAME,
            'app_version' => ApplicationService::APP_VERSION,
            'app_full_name' => ApplicationService::APP_FULL_NAME,
            'app_description' => ApplicationService::APP_DESCRIPTION,
            // owner
            'app_owner_name' => ApplicationService::OWNER_NAME,
            'app_owner_email' => ApplicationService::OWNER_EMAIL,
            'app_owner_url' => ApplicationService::OWNER_URL,
            // links
            'link_dev' => '%link_dev%',
            'link_prod' => '%link_prod%',
            // cokies
            'cookie_path' => '%cookie_path%',
            // services and parameters
            'index_service' => '@' . IndexService::class,
            'user_parameters' => '@' . UserParameters::class,
            'application_parameters' => '@' . ApplicationParameters::class,
        ],
    ],
    'when@test' => [
        'twig' => [
            'strict_variables' => true,
        ],
    ],
]);
