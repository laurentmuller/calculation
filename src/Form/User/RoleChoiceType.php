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

namespace App\Form\User;

use App\Form\AbstractChoiceType;
use App\Interfaces\RoleInterface;

/**
 * A single role choice type.
 *
 * @author Laurent Muller
 */
class RoleChoiceType extends AbstractChoiceType
{
    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return [
            'user.roles.user' => RoleInterface::ROLE_USER,
            'user.roles.admin' => RoleInterface::ROLE_ADMIN,
            'user.roles.super_admin' => RoleInterface::ROLE_SUPER_ADMIN,
        ];
    }
}
