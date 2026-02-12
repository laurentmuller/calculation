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

namespace App\Interfaces;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

/**
 * Extends the user interface.
 */
interface UserInterface extends BaseUserInterface, PasswordAuthenticatedUserInterface, ResetPasswordRequestInterface, RoleInterface
{
    /** The maximum length for a username property. */
    public const int MAX_USERNAME_LENGTH = 180;

    /** The minimum length for the password. */
    public const int MIN_PASSWORD_LENGTH = 6;

    /** The minimum length for a username property. */
    public const int MIN_USERNAME_LENGTH = 2;
}
