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
    /** @var non-negative-int|null */
    #[Parameter('admin_rights')]
    private ?int $adminRights = null;

    private ?RoleBuilderService $service = null;

    /** @var non-negative-int|null */
    #[Parameter('user_rights')]
    private ?int $userRights = null;

    public function getAdminRights(): ?int
    {
        return $this->adminRights;
    }

    public function getAdminRole(): Role
    {
        return $this->updateRole($this->getDefaultAdminRole(), $this->adminRights);
    }

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_rights';
    }

    public function getDefaultAdminRole(): Role
    {
        return $this->getService()->getAdminRole();
    }

    public function getDefaultUserRole(): Role
    {
        return $this->getService()->getUserRole();
    }

    public function getUserRights(): ?int
    {
        return $this->userRights;
    }

    public function getUserRole(): Role
    {
        return $this->updateRole($this->getDefaultUserRole(), $this->userRights);
    }

    public function resetAdminRights(): self
    {
        return $this->setAdminRights(null);
    }

    public function resetUserRights(): self
    {
        return $this->setUserRights(null);
    }

    /**
     * @param Role|non-negative-int|null $adminRights
     */
    public function setAdminRights(Role|int|null $adminRights): self
    {
        $this->adminRights = $this->cleanRights($adminRights, $this->getDefaultAdminRole());

        return $this;
    }

    /**
     * @param Role|non-negative-int|null $userRights
     */
    public function setUserRights(Role|int|null $userRights): self
    {
        $this->userRights = $this->cleanRights($userRights, $this->getDefaultUserRole());

        return $this;
    }

    /**
     * @param Role|non-negative-int|null $rights
     *
     * @return non-negative-int|null
     */
    private function cleanRights(Role|int|null $rights, Role $default): ?int
    {
        if ($rights instanceof Role) {
            $rights = $rights->getRights();
        }
        if (null === $rights || 0 === $rights || $rights === $default->getRights()) {
            return null;
        }

        return $rights;
    }

    private function getService(): RoleBuilderService
    {
        return $this->service ??= new RoleBuilderService();
    }

    /**
     * @param non-negative-int|null $rights
     */
    private function updateRole(Role $role, ?int $rights): Role
    {
        return null !== $rights ? $role->setRights($rights) : $role;
    }
}
