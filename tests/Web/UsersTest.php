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

namespace App\Tests\Web;

/**
 * Test class for users.
 *
 * @author Laurent Muller
 */
class UsersTest extends AuthenticateWebTestCase
{
    public function getUserExist(): array
    {
        return [
            [self::ROLE_USER, true],
            [self::ROLE_ADMIN, true],
            [self::ROLE_SUPER_ADMIN, true],
            [self::ROLE_DISABLED, true],
            [self::ROLE_FAKE, false],
        ];
    }

    public function getUserRole(): array
    {
        return [
            [self::ROLE_USER],
            [self::ROLE_ADMIN],
            [self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @dataProvider getUserExist
     */
    public function testUserExist(string $username, bool $exist): void
    {
        $user = $this->loadUser($username, false);
        if ($exist) {
            $this->assertNotNull($user, "The user '$username' is null.");
        } else {
            $this->assertNull($user, "The user '$username' is not null.");
        }
    }

    /**
     * @dataProvider getUserRole
     */
    public function testUserRole(string $username): void
    {
        $user = $this->loadUser($username, false);
        $this->assertNotNull($user, "The user '$username' is null.");
        $this->assertTrue($user->hasRole($username));
    }
}
