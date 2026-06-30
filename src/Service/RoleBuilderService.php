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

namespace App\Service;

use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Model\Role;

/**
 * Service to build roles with default access rights.
 */
class RoleBuilderService
{
    /**
     * Gets the admin role ('ROLE_ADMIN').
     */
    public function getAdminRole(): Role
    {
        return $this->getRoleWithAll(RoleInterface::ROLE_ADMIN);
    }

    /**
     * Gets the disabled role.
     */
    public function getDisabledRole(): Role
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setOverwrite(true);

        return $role;
    }

    /**
     * Gets a role for the given user.
     */
    public function getRole(User $user): Role
    {
        if (!$user->isEnabled()) {
            return $this->getDisabledRole();
        }
        if ($user->isSuperAdmin()) {
            return $this->getSuperAdminRole();
        }
        if ($user->isAdmin()) {
            return $this->getAdminRole();
        }

        return $this->getUserRole();
    }

    /**
     * Gets the super admin role ('ROLE_SUPER_ADMIN').
     */
    public function getSuperAdminRole(): Role
    {
        return $this->getRoleWithAll(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Gets the user role ('ROLE_USER').
     */
    public function getUserRole(): Role
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setPermission(EntityName::CALCULATION, EntityPermission::getAllPermission());
        $role->setPermissions(
            EntityPermission::getDefaultPermission(),
            EntityName::CALCULATION_STATE,
            EntityName::GROUP,
            EntityName::CATEGORY,
            EntityName::PRODUCT,
            EntityName::TASK,
            EntityName::CUSTOMER,
            EntityName::GLOBAL_MARGIN,
        );
        $role->setPermissions(
            EntityPermission::getNonePermission(),
            EntityName::USER,
            EntityName::LOG
        );

        return $role;
    }

    /**
     * @phpstan-param RoleInterface::ROLE_* $roleName
     */
    private function getRoleWithAll(string $roleName): Role
    {
        $role = new Role($roleName);

        return $role->setPermissions(
            EntityPermission::getAllPermission(),
            ...EntityName::cases()
        );
    }
}
