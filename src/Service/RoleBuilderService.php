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
use Elao\Enum\FlagBag;

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
        $all = $this->getAllPermissions();
        $none = $this->getNonePermissions();
        $default = $this->getDefaultPermissions();
        $role = new Role(RoleInterface::ROLE_USER);
        $role->EntityCalculation = $all;
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
     * @return FlagBag<EntityPermission>
     */
    private function getAllPermissions(): FlagBag
    {
        return FlagBag::from(...EntityPermission::sorted());
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    private function getDefaultPermissions(): FlagBag
    {
        return FlagBag::from(
            EntityPermission::LIST,
            EntityPermission::EXPORT,
            EntityPermission::SHOW
        );
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    private function getNonePermissions(): FlagBag
    {
        return new FlagBag(EntityPermission::class);
    }

    private function getRoleWithAll(string $roleName): Role
    {
        $role = new Role($roleName);
        $value = $this->getAllPermissions();
        $entities = EntityName::constants();
        foreach ($entities as $entity) {
            $role->$entity = $value;
        }

        return $role;
    }
}
