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

use App\Interfaces\RoleInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for user's roles.
 */
#[CoversNothing]
final class UsersTest extends AuthenticateWebTestCase
{
    public static function getUserExist(): \Generator
    {
        yield [self::ROLE_USER];
        yield [self::ROLE_ADMIN];
        yield [self::ROLE_SUPER_ADMIN];
        yield [self::ROLE_DISABLED];
    }

    public static function getUserNotExist(): \Generator
    {
        yield [self::ROLE_FAKE];
    }

    public static function getUserRole(): \Generator
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

    #[DataProvider('getUserNotExist')]
    public function testUserNotExist(string $username): void
    {
        $user = $this->loadUser($username);
        self::assertNull($user);
    }

    /**
     * @phpstan-param RoleInterface::ROLE_* $username
     */
    #[DataProvider('getUserRole')]
    public function testUserRole(string $username): void
    {
        $user = $this->loadUser($username);
        self::assertNotNull($user);
        self::assertTrue($user->hasRole($username));
    }
}
