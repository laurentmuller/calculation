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
 * Test class for users.
 *
 * @author Laurent Muller
 */
class UsersTest extends AbstractAuthenticateWebTestCase
{
    /**
     * @return array<int, array<int, bool|string>>
     */
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

    /**
     * @return array<int, array<int, string>>
     */
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
