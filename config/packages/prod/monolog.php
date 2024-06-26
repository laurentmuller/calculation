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

use Psr\Log\LogLevel;
use Symfony\Config\MonologConfig;

return static function (MonologConfig $config): void {
    $config->channels(['deprecation']);

    $handler = $config->handler('main')
        ->type('fingers_crossed')
        ->actionLevel(LogLevel::ERROR)
        ->handler('nested')
        ->bufferSize(50);
    $handler->excludedHttpCode()
        ->code(404);
    $handler->excludedHttpCode()
        ->code(405);

    $config->handler('nested')
        ->type('stream')
        ->path('%kernel.logs_dir%/%kernel.environment%.log')
        ->level(LogLevel::INFO)
        ->formatter('monolog.custom_formatter');

    $config->handler('console')
        ->type('console')
        ->processPsr3Messages(false)
        ->channels()
        ->elements(['!event', '!doctrine', '!console', '!deprecation']);

    $config->handler('deprecation')
        ->type('stream')
        ->path('%kernel.logs_dir%/%kernel.environment%.deprecations.log')
        ->formatter('monolog.custom_formatter')
        ->channels()
        ->elements(['deprecation']);
};
