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
        $role = $this->getService()->getRoleAdmin();
        if (null !== $this->adminRights) {
            $role->setRights($this->adminRights);
        }

        return $role;
    }

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_rights';
    }

    /**
     * @return non-negative-int
     */
    public function getDefaultAdminRights(): int
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
     * @return non-negative-int
     */
    public function getDefaultUserRights(): int
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
        if (null !== $this->userRights) {
            $role->setRights($this->userRights);
        }

        return $role;
    }

    /**
     * @param non-negative-int|null $adminRights
     */
    public function setAdminRights(?int $adminRights): self
    {
        $this->adminRights = $this->cleanRights($adminRights, $this->getDefaultAdminRights());

        return $this;
    }

    /**
     * @param non-negative-int|null $userRights
     */
    public function setUserRights(?int $userRights): self
    {
        $this->userRights = $this->cleanRights($userRights, $this->getDefaultUserRights());

        return $this;
    }

    /**
     * @param non-negative-int|null $rights
     *
     * @return non-negative-int|null
     */
    private function cleanRights(?int $rights, int $default): ?int
    {
        if (null === $rights || $rights === $default) {
            return null;
        }

        return $rights;
    }
}
