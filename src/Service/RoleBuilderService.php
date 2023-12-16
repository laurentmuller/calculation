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
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Model\Role;

/**
 * Service to build roles with default access rights.
 */
class RoleBuilderService
{
    /**
     * Gets a role with default access rights for the given user.
     */
    public function getRole(User $user): Role
    {
        if (!$user->isEnabled()) {
            return $this->getRoleDisabled();
        }
        if ($user->isSuperAdmin()) {
            return $this->getRoleSuperAdmin();
        }
        if ($user->isAdmin()) {
            return $this->getRoleAdmin();
        }

        return $this->getRoleUser();
    }

    /**
     * Gets the admin role ('ROLE_ADMIN') with default access rights.
     */
    public function getRoleAdmin(): Role
    {
        return $this->getRoleWithAll(RoleInterface::ROLE_ADMIN);
    }

    /**
     * Gets disabled role with the no access right.
     */
    public function getRoleDisabled(): Role
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setOverwrite(true);

        return $role;
    }

    /**
     * Gets the super admin role ('ROLE_SUPER_ADMIN') with default access rights.
     */
    public function getRoleSuperAdmin(): Role
    {
        return $this->getRoleWithAll(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Gets the user role ('ROLE_USER') with the default access rights.
     */
    public function getRoleUser(): Role
    {
        $all = EntityPermission::getAllPermission();
        $none = EntityPermission::getNonePermission();
        $default = EntityPermission::getDefaultPermission();
        $role = new Role(RoleInterface::ROLE_USER);

        return $role->setCalculationPermission($all)
            ->setCalculationStatePermission($default)
            ->setCategoryPermission($default)
            ->setCustomerPermission($default)
            ->setGlobalMarginPermission($default)
            ->setGroupPermission($default)
            ->setLogPermission($none)
            ->setProductPermission($default)
            ->setTaskPermission($default)
            ->setUserPermission($none);
    }

    private function getRoleWithAll(string $roleName): Role
    {
        $all = EntityPermission::getAllPermission();
        $role = new Role($roleName);

        return $role->setCalculationPermission($all)
            ->setCalculationStatePermission($all)
            ->setCategoryPermission($all)
            ->setCustomerPermission($all)
            ->setGlobalMarginPermission($all)
            ->setGroupPermission($all)
            ->setLogPermission($all)
            ->setProductPermission($all)
            ->setTaskPermission($all)
            ->setUserPermission($all);
    }
}
