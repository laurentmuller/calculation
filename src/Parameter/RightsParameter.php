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
    /** @var int[]|null */
    #[Parameter('admin_rights')]
    private ?array $adminRights = null;

    private ?RoleBuilderService $service = null;

    /** @var int[]|null */
    #[Parameter('user_rights')]
    private ?array $userRights = null;

    /**
     * @return int[]|null
     */
    public function getAdminRights(): ?array
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

    /**
     * @return int[]
     */
    public function getDefaultAdminRights(): array
    {
        return $this->getDefaultAdminRole()
            ->getRights();
    }

    public function getDefaultAdminRole(): Role
    {
        return $this->getService()
            ->getRoleAdmin();
    }

    /**
     * @return int[]
     */
    public function getDefaultUserRights(): array
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

    /**
     * @return int[]|null
     */
    public function getUserRights(): ?array
    {
        return $this->userRights;
    }

    public function getUserRole(): Role
    {
        $role = $this->getService()->getRoleUser();
        $role->setRights($this->userRights ?? $role->getRights());

        return $role;
    }

    /**
     * @param int[]|null $adminRights
     */
    public function setAdminRights(?array $adminRights): self
    {
        $this->adminRights = $this->cleanRights($adminRights, $this->getDefaultAdminRights());

        return $this;
    }

    public function setAdminRole(Role $role): self
    {
        return $this->setAdminRights($role->getRights());
    }

    /**
     * @param int[]|null $userRights
     */
    public function setUserRights(?array $userRights): self
    {
        $this->userRights = $this->cleanRights($userRights, $this->getDefaultUserRights());

        return $this;
    }

    public function setUserRole(Role $role): self
    {
        return $this->setUserRights($role->getRights());
    }

    /**
     * @param int[]|null $rights
     * @param int[]      $default
     *
     * @return int[]|null
     */
    private function cleanRights(?array $rights, array $default): ?array
    {
        if (null === $rights || [] === $rights || 0 === \array_sum($rights) || $rights === $default) {
            return null;
        }

        return $rights;
    }
}
