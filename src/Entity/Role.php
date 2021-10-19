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

namespace App\Entity;

use App\Interfaces\RoleInterface;
use App\Traits\RightsTrait;

/**
 * Implementation of the role interface with access rights.
 *
 * @author Laurent Muller
 */
class Role implements RoleInterface
{
    use RightsTrait;

    /**
     * The name.
     */
    protected ?string $name = null;

    /**
     * The role.
     */
    protected string $role;

    /**
     * Constructor.
     *
     * @param string $role the role
     * @param string $name the name
     */
    public function __construct(string $role, string $name = null)
    {
        $this->role = $role;
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->getRole();
    }

    /**
     * Gets the display name or the role name if not defined.
     */
    public function getName(): string
    {
        return $this->name ?? $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole(): string
    {
        return $this->role;
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
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(RoleInterface::ROLE_ADMIN);
    }

    /**
     * {@inheritdoc}
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Sets the display name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
