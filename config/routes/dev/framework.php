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

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $config): void {
    if ('dev' === $config->env()) {
        $config->import('@FrameworkBundle/Resources/config/routing/errors.xml')->prefix('/_error');
        $config->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')->prefix('/_wdt');
        $config->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')->prefix('/_profiler');
    }
};
