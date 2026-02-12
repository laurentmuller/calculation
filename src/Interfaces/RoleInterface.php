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

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use Elao\Enum\FlagBag;

/**
 * Class implementing this interface deals with role names.
 */
interface RoleInterface
{
    /** The administrator role name. */
    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    /** The site administrator role name. */
    public const string ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /** The user role name (default). */
    public const string ROLE_USER = 'ROLE_USER';

    /**
     * Gets the permission for the given entity name.
     *
     * @return FlagBag<EntityPermission>
     */
    public function getPermission(EntityName $entity): FlagBag;

    /**
     * Gets the role.
     *
     * @phpstan-return self::ROLE_*
     */
    public function getRole(): string;

    /**
     * Gets roles.
     *
     * @return string[]
     *
     * @phpstan-return self::ROLE_*[]
     */
    public function getRoles(): array;

    /**
     * Checks if this has a given role.
     *
     * @param string $role the role name to be tested
     *
     * @phpstan-param self::ROLE_* $role
     *
     * @return bool true if this has a given role
     */
    public function hasRole(string $role): bool;

    /**
     * Tells if this has the admin or the super admin role.
     */
    public function isAdmin(): bool;

    /**
     * Tells if this has the super admin role.
     */
    public function isSuperAdmin(): bool;

    /**
     * Sets the permission for the given entity name.
     *
     * @param FlagBag<EntityPermission> $permission
     */
    public function setPermission(EntityName $entity, FlagBag $permission): static;
}
