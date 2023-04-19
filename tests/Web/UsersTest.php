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
class UsersTest extends AbstractAuthenticateWebTestCase
{
    /**
     * @return array<int, array<int, bool|string>>
     */
    public static function getUserExist(): array
    {
        return [
            [self::ROLE_USER, true],
            [self::ROLE_ADMIN, true],
            [self::ROLE_SUPER_ADMIN, true],
            [self::ROLE_DISABLED, true],
            [self::ROLE_FAKE, false],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getUserRole(): array
    {
        return [
            [self::ROLE_USER],
            [self::ROLE_ADMIN],
            [self::ROLE_SUPER_ADMIN],
        ];
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
