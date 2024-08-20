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
use Symfony\Config\MonologConfig;

return static function (MonologConfig $config): void {
    $config->handler('main')
        ->type('stream')
        ->path('%kernel.logs_dir%/%kernel.environment%.log')
        ->formatter(LogService::FORMATTER_NAME)
        ->channels()
        ->elements(['app']);
};
