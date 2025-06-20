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

namespace App\Model;

use App\Interfaces\RoleInterface;
use App\Traits\RoleTrait;

/**
 * Implementation of the role interface with access rights.
 */
class Role implements \Stringable, RoleInterface
{
    use RoleTrait;

    /**
     * @param string  $role   the role
     * @param ?string $name   the optional name
     * @param ?int[]  $rights the optional rights
     *
     * @phpstan-param RoleInterface::ROLE_* $role
     */
    public function __construct(string $role, protected ?string $name = null, ?array $rights = null)
    {
        $this->role = $role;
        $this->setRights($rights);
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getRole();
    }

    /**
     * Gets the display name or the role name if not defined.
     */
    public function getName(): string
    {
        return $this->name ?? $this->getRole();
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
