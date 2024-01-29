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

use Symfony\Config\DebugConfig;

return static function (DebugConfig $config): void {
    $config->dumpDestination('tcp://%env(VAR_DUMPER_SERVER)%');
};
