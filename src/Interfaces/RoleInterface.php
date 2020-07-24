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

namespace App\Interfaces;

/**
 * Class implementing this interface deals with role names.
 *
 * @author Laurent Muller
 */
interface RoleInterface
{
    /**
     * The administrator role name.
     */
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * The super administrator role name.
     */
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * The user role name.
     */
    public const ROLE_USER = 'ROLE_USER';

    /**
     * Gets the role.
     */
    public function getRole(): string;

    /**
     * Checks if this has the given role.
     *
     * @param string $role the role name to be tested
     *
     * @return bool true if this has the given role
     */
    public function hasRole(string $role): bool;

    /**
     * Tells if this has the admin role.
     */
    public function isAdmin(): bool;

    /**
     * Tells if this has the super admin role.
     */
    public function isSuperAdmin(): bool;
}
