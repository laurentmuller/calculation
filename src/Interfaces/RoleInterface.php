<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Class implementing this interface deals with role names.
 *
 * @author Laurent Muller
 */
interface RoleInterface
{
    /**
     * The administrator role name.
     */
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * The super administrator role name.
     */
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * The user role name.
     */
    public const ROLE_USER = 'ROLE_USER';

    /**
     * Gets the role.
     */
    public function getRole(): string;

    /**
     * Checks if this has the given role.
     *
     * @param string $role the role name to be tested
     *
     * @return bool true if this has the given role
     */
    public function hasRole(string $role): bool;

    /**
     * Tells if this has the admin role.
     */
    public function isAdmin(): bool;

    /**
     * Tells if this has the super admin role.
     */
    public function isSuperAdmin(): bool;
}
