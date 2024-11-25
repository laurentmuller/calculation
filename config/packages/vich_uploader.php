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

use App\Service\UserNamer;
use Symfony\Config\VichUploaderConfig;

return static function (VichUploaderConfig $config): void {
    $config->dbDriver('orm')
        ->mappings('user_image')
        ->namer(UserNamer::class)
        ->uriPrefix('/images/users')
        ->uploadDestination('%kernel.project_dir%/public/images/users');
};
