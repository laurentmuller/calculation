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

use App\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $config): void {
    $config->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('plaintext');
    $config->passwordHasher(User::class)
        ->algorithm('plaintext');
};
