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

/**
 * Test class for user's roles.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class UsersTest extends AbstractAuthenticateWebTestCase
{
    public static function getUserExist(): \Iterator
    {
        yield [self::ROLE_USER, true];
        yield [self::ROLE_ADMIN, true];
        yield [self::ROLE_SUPER_ADMIN, true];
        yield [self::ROLE_DISABLED, true];
        yield [self::ROLE_FAKE, false];
    }

    public static function getUserRole(): \Iterator
    {
        yield [self::ROLE_USER];
        yield [self::ROLE_ADMIN];
        yield [self::ROLE_SUPER_ADMIN];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUserExist')]
    public function testUserExist(string $username, bool $exist): void
    {
        $user = $this->loadUser($username, false);
        if ($exist) {
            self::assertNotNull($user, "The user '$username' is null.");
        } else {
            self::assertNull($user, "The user '$username' is not null.");
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUserRole')]
    public function testUserRole(string $username): void
    {
        /** @psalm-var \App\Interfaces\RoleInterface::ROLE_* $role */
        $role = $username;
        $user = $this->loadUser($username, false);
        self::assertNotNull($user);
        self::assertTrue($user->hasRole($role));
    }
}
