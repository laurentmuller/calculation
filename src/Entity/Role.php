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
     * The role name.
     *
     * @var string
     */
    protected $name;

    /**
     * The role.
     *
     * @var string
     */
    protected $role;

    /**
     * Constructor.
     *
     * @param string $role the role
     */
    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function __toString(): string
    {
        return $this->role;
    }

    /**
     * Gets the display name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole(): string
    {
        return $this->role;
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
