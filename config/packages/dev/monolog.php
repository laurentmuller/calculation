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
use Psr\Log\LogLevel;
use Symfony\Config\MonologConfig;

return static function (MonologConfig $config): void {
    $config->channels(['deprecation']);

    $config->handler('main')
        ->type('stream')
        ->path('%kernel.logs_dir%/%kernel.environment%.log')
        ->level(LogLevel::DEBUG)
        ->formatter(LogService::FORMATTER_NAME)
        ->channels()
        ->elements(['!event']);

    $config->handler('console')
        ->type('console')
        ->processPsr3Messages(false)
        ->channels()
        ->elements(['!event', '!doctrine', '!console', '!deprecation']);

    $config->handler('deprecation')
        ->type('stream')
        ->path('%kernel.logs_dir%/%kernel.environment%.deprecations.log')
        ->formatter(LogService::FORMATTER_NAME)
        ->channels()
        ->elements(['deprecation']);
};
