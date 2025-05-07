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
     * Gets the disabled role with the no access right.
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
        $role->CalculationRights = $all;
        $role->CalculationStateRights = $default;
        $role->GroupRights = $default;
        $role->CategoryRights = $default;
        $role->ProductRights = $default;
        $role->TaskRights = $default;
        $role->GlobalMarginRights = $default;
        $role->UserRights = $none;
        $role->LogRights = $none;
        $role->CustomerRights = $default;

        return $role;
    }

    /**
     * @phpstan-param RoleInterface::ROLE_* $roleName
     */
    private function getRoleWithAll(string $roleName): Role
    {
        $role = new Role($roleName);
        $entities = EntityName::sorted();
        $all = EntityPermission::getAllPermission();
        foreach ($entities as $entity) {
            $field = $entity->getRightsField();
            $role->__set($field, $all);
        }

        return $role;
    }
}
