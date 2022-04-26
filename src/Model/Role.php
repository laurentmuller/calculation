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
use App\Traits\RightsTrait;
use App\Traits\RoleTrait;

/**
 * Implementation of the role interface with access rights.
 */
class Role implements RoleInterface, \Stringable
{
    use RightsTrait;
    use RoleTrait;

    /**
     * Constructor.
     *
     * @param string      $role the role
     * @param string|null $name the optional name
     */
    public function __construct(string $role, protected ?string $name = null)
    {
        $this->role = $role;
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
