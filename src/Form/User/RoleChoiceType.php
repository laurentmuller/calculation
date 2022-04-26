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

use App\Entity\User;
use App\Form\AbstractChoiceType;
use App\Interfaces\RoleInterface;
use Symfony\Component\Security\Core\Security;

/**
 * A single role choice type.
 */
class RoleChoiceType extends AbstractChoiceType
{
    private readonly bool $superAdmin;

    /**
     * Constructor.
     */
    public function __construct(Security $security)
    {
        $user = $security->getUser();
        $this->superAdmin = $user instanceof User && $user->isSuperAdmin();
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
        if ($this->superAdmin) {
            $choices['user.roles.super_admin'] = RoleInterface::ROLE_SUPER_ADMIN;
        }

        return $choices;
    }
}
