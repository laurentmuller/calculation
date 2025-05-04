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

namespace App\Tests\Form\User;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\PasswordHasher\EventListener\PasswordHasherListener;
use Symfony\Component\Form\Extension\PasswordHasher\PasswordHasherExtension;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

/**
 * @phpstan-require-extends TestCase
 */
trait PasswordHasherExtensionTrait
{
    protected function getPasswordHasherExtension(): PasswordHasherExtension
    {
        $passwordHasher = $this->createMock(UserPasswordHasher::class);
        $listener = new PasswordHasherListener($passwordHasher);

        return new PasswordHasherExtension($listener);
    }
}
