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

namespace App\Traits;

use App\Interfaces\RoleInterface;

/**
 * A trait to get translated and icon role.
 */
trait RoleTranslatorTrait
{
    use TranslatorTrait;

    /**
     * Gets the role's icon.
     *
     * @psalm-api
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
     * Gets the translated role.
     */
    public function translateRole(RoleInterface|string $role): string
    {
        if ($role instanceof RoleInterface) {
            $role = $role->getRole();
        }
        $id = \strtolower(\str_ireplace('role_', 'user.roles.', $role));

        return $this->trans($id);
    }
}
