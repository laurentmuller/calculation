<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Traits;

use App\Interfaces\RoleInterface;

/**
 * Trait to translate role.
 *
 * @author Laurent Muller
 */
trait RoleTranslatorTrait
{
    use TranslatorTrait;

    /**
     * Translate the given role.
     */
    public function translateRole(string|RoleInterface $role): string
    {
        if ($role instanceof RoleInterface) {
            $role = $role->getRole();
        }
        $role = \strtolower(\str_replace('ROLE_', 'user.roles.', $role));

        return $this->trans($role);
    }
}
