<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Traits\RightsTrait;
use Symfony\Component\Security\Core\Role\Role as BaseRole;

/**
 * Extends the role with access rights.
 *
 * @author Laurent Muller
 */
class Role extends BaseRole
{
    use RightsTrait;

    /**
     * The role name.
     *
     * @var string
     */
    protected $name;

    /**
     * Constructor.
     *
     * @param string $role the role name
     */
    public function __construct(string $role)
    {
        parent::__construct($role);
    }

    public function __toString(): string
    {
        return (string) $this->getRole();
    }

    /**
     * Gets the display name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Checks if this has the given role.
     *
     * @param string $role the role name to be tested
     *
     * @return bool true if this has the given role
     */
    public function hasRole(string $role): bool
    {
        return 0 === \strcasecmp($role, $this->getRole());
    }

    /**
     * Tells if this has the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(User::ROLE_ADMIN);
    }

    /**
     * Tells if this has the super admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(User::ROLE_SUPER_ADMIN);
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
