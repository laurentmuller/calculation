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

use App\Service\AssetVersionService;
use App\Utils\FormatUtils;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->secret('%app_secret%')
        ->httpMethodOverride(true);

    $config->defaultLocale(FormatUtils::DEFAULT_LOCALE)
        ->enabledLocales([FormatUtils::DEFAULT_LOCALE]);

    $config->session()
        ->name('CALCULATION_SESSION_ID')
        ->cookiePath('%cookie_path%')
        ->enabled(true);

    $config->mailer()
        ->dsn('%env(MAILER_DSN)%')
        ->envelope()
        ->sender('%mailer_user_email%');

    $config->assets()
        ->versionStrategy(AssetVersionService::class)
        ->strictMode('%kernel.debug%');

    $config->router()
        ->utf8(true);

    $config->phpErrors()
        ->log();

    $config->form()
        ->csrfProtection()
        ->fieldName('csrf_token');

    $config->propertyInfo()
        ->withConstructorExtractor(true);
};
