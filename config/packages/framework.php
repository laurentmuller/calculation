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
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->secret('%app_secret%')
        ->httpMethodOverride(true);

    $config->session()
        ->enabled(true)
        ->cookiePath('%cookie_path%');

    $config->assets()
        ->versionStrategy(AssetVersionService::class);

    $config->router()
        ->utf8(true);

    $config->phpErrors()
        ->log();

    $config->form()
        ->csrfProtection()
        ->fieldName('token');
};
