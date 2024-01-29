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

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestMethod\CurlPost;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $config): void {
    $services = $config->services();

    $services->alias(RequestMethod::class, CurlPost::class);

    $services->set(ReCaptcha::class)
        ->arg('$secret', '%google_recaptcha_secret_key%')
        ->arg('$requestMethod', service(RequestMethod::class));
};
