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
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;

/**
 * Service to deal with roles and hierarchy.
 */
readonly class RoleService
{
    public function __construct(
        private RoleHierarchyInterface $service,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Gets the reachable role names for the given data.
     *
     * @param ?RoleInterface $role the data to get reachable role names
     *
     * @return string[] an array, maybe empty, of reachable role names
     *
     * @phpstan-return RoleInterface::ROLE_*[]
     */
    public function getReachableRoleNames(?RoleInterface $role): array
    {
        $roles = $this->getRoleNames($role);

        /** @phpstan-var RoleInterface::ROLE_*[] */
        return [] === $roles ? $roles : $this->service->getReachableRoleNames($roles);
    }

    /**
     * Gets the role's icon.
     */
    public function getRoleIcon(RoleInterface|string $role): string
    {
        if ($role instanceof RoleInterface) {
            $role = $role->getRole();
        }

        return match ($role) {
            RoleInterface::ROLE_SUPER_ADMIN => 'fa-solid fa-user-gear',
            RoleInterface::ROLE_ADMIN => 'fa-solid fa-user-shield',
            default => 'fa-solid fa-user',
        };
    }

    /**
     * Gets the icon and the translated role.
     */
    #[AsTwigFilter('role_icon_name')]
    public function getRoleIconAndName(RoleInterface|string $role): string
    {
        return \sprintf(
            '<i class="me-1 %s"></i>%s',
            $this->getRoleIcon($role),
            $this->translateRole($role)
        );
    }

    /**
     * Gets the role names for the given data.
     *
     * @return string[] an array, maybe empty, of role names
     *
     * @phpstan-return RoleInterface::ROLE_*[]
     */
    public function getRoleNames(?RoleInterface $role): array
    {
        if ($role instanceof RoleInterface) {
            return $role->getRoles();
        }

        return [];
    }

    /**
     * Returns if the given data has the given role name.
     *
     * @param ?RoleInterface $role     the data to get role names
     * @param string         $roleName the role name to verify
     *
     * @return bool true if the given data has the given role name; false otherwise
     *
     * @phpstan-param RoleInterface::ROLE_* $roleName
     */
    public function hasRole(?RoleInterface $role, string $roleName): bool
    {
        $roles = $this->getReachableRoleNames($role);

        return [] !== $roles && \in_array($roleName, $roles, true);
    }

    /**
     * Translate the enabled state.
     */
    public function translateEnabled(bool $enabled): string
    {
        return $this->translator->trans($enabled ? 'common.value_enabled' : 'common.value_disabled');
    }

    /**
     * Gets the translated role.
     */
    public function translateRole(RoleInterface|string $role): string
    {
        if ($role instanceof RoleInterface) {
            $role = $role->getRole();
        }
        $id = \strtolower(\str_ireplace('role_', 'user.roles.', $role));

        return $this->translator->trans($id);
    }
}
