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

namespace App\Form\User;

use App\Form\AbstractChoiceType;
use App\Interfaces\RoleInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * A single role choice type.
 */
class RoleChoiceType extends AbstractChoiceType
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Security $security)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        $choices = [
            'user.roles.user' => RoleInterface::ROLE_USER,
            'user.roles.admin' => RoleInterface::ROLE_ADMIN,
        ];
        if ($this->isSuperAdmin()) {
            $choices['user.roles.super_admin'] = RoleInterface::ROLE_SUPER_ADMIN;
        }

        return $choices;
    }

    private function isSuperAdmin(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof RoleInterface && $user->isSuperAdmin();
    }
}
