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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait for class implementing <code>RoleInterface</code>.
 *
 * @phpstan-require-implements RoleInterface
 */
trait RoleTrait
{
    use RightsTrait;

    /**
     * The role name.
     *
     * @phpstan-var RoleInterface::ROLE_*|null
     */
    #[Assert\Length(max: 25)]
    #[Assert\Choice([RoleInterface::ROLE_USER, RoleInterface::ROLE_ADMIN, RoleInterface::ROLE_SUPER_ADMIN])]
    #[ORM\Column(length: 25, nullable: true)]
    private ?string $role = null; // @phpstan-ignore doctrine.columnType

    /**
     * Gets the role.
     *
     * @see RoleInterface
     *
     * @phpstan-return RoleInterface::ROLE_*
     */
    public function getRole(): string
    {
        return $this->role ?? RoleInterface::ROLE_USER;
    }

    /**
     * @return string[]
     *
     * @see UserInterface
     *
     * @phpstan-return RoleInterface::ROLE_*[]
     */
    public function getRoles(): array
    {
        return [$this->getRole()];
    }

    /**
     * @see RoleInterface
     *
     * @phpstan-param RoleInterface::ROLE_* $role
     */
    public function hasRole(string $role): bool
    {
        return $role === $this->getRole();
    }

    /**
     * @see RoleInterface
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->hasRole(RoleInterface::ROLE_ADMIN);
    }

    /**
     * @see RoleInterface
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Sets the role.
     *
     * @phpstan-param RoleInterface::ROLE_*|null $role
     */
    public function setRole(?string $role): static
    {
        $this->role = RoleInterface::ROLE_USER === $role ? null : $role;

        return $this;
    }
}
