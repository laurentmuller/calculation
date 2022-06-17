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
 * Trait to deal with a role name and rights.
 */
trait RoleTrait
{
    use RightsTrait;

    /**
     * The role name.
     */
    #[Assert\Length(max: 25)]
    #[Assert\Choice([RoleInterface::ROLE_USER, RoleInterface::ROLE_ADMIN, RoleInterface::ROLE_SUPER_ADMIN])]
    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    protected ?string $role = null;

    /**
     * Gets the role.
     *
     * @see RoleInterface
     */
    public function getRole(): string
    {
        return $this->role ?? RoleInterface::ROLE_USER;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return [$this->getRole()];
    }

    /**
     * {@inheritdoc}
     *
     * @see RoleInterface
     */
    public function hasRole(string $role): bool
    {
        return 0 === \strcasecmp($role, $this->getRole());
    }

    /**
     * {@inheritdoc}
     *
     * @see RoleInterface
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->hasRole(RoleInterface::ROLE_ADMIN);
    }

    /**
     * {@inheritdoc}
     *
     * @see RoleInterface
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Sets role.
     */
    public function setRole(?string $role): static
    {
        $this->role = RoleInterface::ROLE_USER === $role ? null : $role;

        return $this;
    }
}
