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

namespace App\Parameter;

use App\Attribute\Parameter;
use App\Model\Role;
use App\Service\RoleBuilderService;

/**
 * Rights parameter.
 */
class RightsParameter implements ParameterInterface
{
    #[Parameter('admin_rights')]
    private ?int $adminRights = null;

    private ?RoleBuilderService $service = null;

    #[Parameter('user_rights')]
    private ?int $userRights = null;

    public function getAdminRights(): ?int
    {
        return $this->adminRights;
    }

    public function getAdminRole(): Role
    {
        $role = $this->getService()->getRoleAdmin();
        $role->setRights($this->adminRights ?? $role->getRights());

        return $role;
    }

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_rights';
    }

    public function getDefaultAdminRights(): ?int
    {
        return $this->getDefaultAdminRole()
            ->getRights();
    }

    public function getDefaultAdminRole(): Role
    {
        return $this->getService()
            ->getRoleAdmin();
    }

    public function getDefaultUserRights(): ?int
    {
        return $this->getDefaultUserRole()
            ->getRights();
    }

    public function getDefaultUserRole(): Role
    {
        return $this->getService()
            ->getRoleUser();
    }

    public function getService(): RoleBuilderService
    {
        return $this->service ??= new RoleBuilderService();
    }

    public function getUserRights(): ?int
    {
        return $this->userRights;
    }

    public function getUserRole(): Role
    {
        $role = $this->getService()->getRoleUser();
        $role->setRights($this->userRights ?? $role->getRights());

        return $role;
    }

    public function setAdminRights(?int $adminRights): self
    {
        $this->adminRights = $this->cleanRights($adminRights, $this->getDefaultAdminRights());

        return $this;
    }

    public function setAdminRole(Role $role): self
    {
        return $this->setAdminRights($role->getRights());
    }

    public function setUserRights(?int $userRights): self
    {
        $this->userRights = $this->cleanRights($userRights, $this->getDefaultUserRights());

        return $this;
    }

    public function setUserRole(Role $role): self
    {
        return $this->setUserRights($role->getRights());
    }

    private function cleanRights(?int $rights, ?int $default): ?int
    {
        if (null === $rights || $rights === $default) {
            return null;
        }

        return $rights;
    }
}
