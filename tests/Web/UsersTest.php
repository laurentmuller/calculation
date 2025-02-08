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

namespace App\Tests\Web;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for user's roles.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class UsersTest extends AuthenticateWebTestCase
{
    public static function getUserExist(): \Iterator
    {
        yield [self::ROLE_USER];
        yield [self::ROLE_ADMIN];
        yield [self::ROLE_SUPER_ADMIN];
        yield [self::ROLE_DISABLED];
    }

    public static function getUserNotExist(): \Iterator
    {
        yield [self::ROLE_FAKE];
    }

    public static function getUserRole(): \Iterator
    {
        yield [self::ROLE_USER];
        yield [self::ROLE_ADMIN];
        yield [self::ROLE_SUPER_ADMIN];
    }

    #[DataProvider('getUserExist')]
    public function testUserExist(string $username): void
    {
        $user = $this->loadUser($username);
        self::assertNotNull($user);
    }

    /**
     * @psalm-param \App\Interfaces\RoleInterface::ROLE_* $username
     */
    #[DataProvider('getUserNotExist')]
    public function testUserNotExist(string $username): void
    {
        $user = $this->loadUser($username);
        self::assertNull($user);
    }

    /**
     * @psalm-param \App\Interfaces\RoleInterface::ROLE_* $username
     */
    #[DataProvider('getUserRole')]
    public function testUserRole(string $username): void
    {
        $user = $this->loadUser($username);
        self::assertNotNull($user);
        self::assertTrue($user->hasRole($username));
    }
}
