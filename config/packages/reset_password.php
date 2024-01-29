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

use App\Repository\UserRepository;
use Symfony\Config\SymfonycastsResetPasswordConfig;

return static function (SymfonycastsResetPasswordConfig $config): void {
    $config->requestPasswordRepository(UserRepository::class)
        ->throttleLimit(300);
};
