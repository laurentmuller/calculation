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

namespace App\Util;

use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use Elao\Enum\FlagBag;

/**
 * Service to build roles with default access rights.
 */
class RoleBuilder
{
    /**
     * The value returned when attribute or entity offset is not found.
     */
    final public const INVALID_VALUE = -1;

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Gets a role with default access rights for the given user.
     */
    public static function getRole(User $user): Role
    {
        if (!$user->isEnabled()) {
            return self::getRoleDisabled();
        }
        if ($user->isSuperAdmin()) {
            return self::getRoleSuperAdmin();
        }
        if ($user->isAdmin()) {
            return self::getRoleAdmin();
        }

        return self::getRoleUser();
    }

    /**
     * Gets the admin role ('ROLE_ADMIN') with default access rights.
     */
    public static function getRoleAdmin(): Role
    {
        return self::getRoleWithAll(RoleInterface::ROLE_ADMIN);
    }

    /**
     * Gets disabled role with the default access rights.
     */
    public static function getRoleDisabled(): Role
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setOverwrite(true);

        return $role;
    }

    /**
     * Gets the super admin role ('ROLE_SUPER_ADMIN') with default access rights.
     */
    public static function getRoleSuperAdmin(): Role
    {
        return self::getRoleWithAll(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Gets the user role ('ROLE_USER') with the default access rights.
     */
    public static function getRoleUser(): Role
    {
        /** @psalm-var FlagBag<EntityPermission> $default */
        $default = FlagBag::from(
            EntityPermission::LIST,
            EntityPermission::EXPORT,
            EntityPermission::SHOW
        );
        $none = new FlagBag(EntityPermission::class, FlagBag::NONE);

        $role = new Role(RoleInterface::ROLE_USER);
        $role->EntityCalculation = self::getFlagBagSorted();
        $role->EntityCalculationState = $default;
        $role->EntityCategory = $default;
        $role->EntityCustomer = $default;
        $role->EntityGlobalMargin = $default;
        $role->EntityGroup = $default;
        $role->EntityLog = $none;
        $role->EntityProduct = $default;
        $role->EntityTask = $default;
        $role->EntityUser = $none;

        return $role;
    }

    /**
     * @psalm-return FlagBag<EntityPermission>
     *
     * @psalm-suppress all
     */
    private static function getFlagBagSorted(): FlagBag
    {
        return FlagBag::from(...EntityPermission::sorted());
    }

    private static function getRoleWithAll(string $roleName): Role
    {
        $role = new Role($roleName);
        $value = self::getFlagBagSorted();
        $entities = EntityName::constants();
        foreach ($entities as $entity) {
            $role->$entity = $value;
        }

        return $role;
    }
}
