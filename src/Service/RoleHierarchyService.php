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

use App\Interfaces\RoleInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Service to deals with roles and hierarchy.
 */
readonly class RoleHierarchyService
{
    /**
     * Constructor.
     */
    public function __construct(private RoleHierarchyInterface $service)
    {
    }

    /**
     * Gets the reachable role names for the given data.
     *
     * @param mixed $data the data to get reachable role names
     *
     * @return string[] an array, maybe empty; of reachable role names
     *
     * @pslam-return RoleInterface::ROLE_*[]
     */
    public function getReachableRoleNames(mixed $data): array
    {
        $roles = $this->getRoleNames($data);

        return [] === $roles ? $roles : $this->service->getReachableRoleNames($roles);
    }

    /**
     * Gets the role names for the given data.
     *
     * @return string[] an array, maybe empty; of role names
     *
     * @pslam-return RoleInterface::ROLE_*[]
     */
    public function getRoleNames(mixed $data): array
    {
        if ($data instanceof UserInterface) {
            return $data->getRoles();
        }
        if ($data instanceof RoleInterface) {
            return $data->getRoles();
        }

        return [];
    }

    /**
     * Returns if the given data has the given role name.
     *
     * @param mixed  $data the data to get role names
     * @param string $role the role name to verify
     *
     * @return bool true if the given data has the given role name; false otherwise
     *
     * @psalm-param RoleInterface::ROLE_* $role
     */
    public function hasRole(mixed $data, string $role): bool
    {
        $roles = $this->getReachableRoleNames($data);

        return [] !== $roles && \in_array($role, $roles, true);
    }
}
