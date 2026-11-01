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

namespace App\Tests\Model;

use App\Interfaces\RoleInterface;
use App\Model\Role;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

final class RoleTest extends TestCase
{
    public function testGetName(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertSame(RoleInterface::ROLE_USER, $role->getName());
        $role = new Role(RoleInterface::ROLE_ADMIN, 'My Name');
        self::assertSame('My Name', $role->getName());
    }

    public function testGetRole(): void
    {
        $role = new Role(RoleInterface::ROLE_ADMIN);
        $role->setRole(null);
        self::assertSame(RoleInterface::ROLE_USER, $role->getRole());
    }

    public function testGetRoles(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertSame([RoleInterface::ROLE_USER], $role->getRoles());
    }

    public function testHasRoles(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertTrue($role->hasRole(RoleInterface::ROLE_USER));
        self::assertFalse($role->hasRole(RoleInterface::ROLE_ADMIN));
        self::assertFalse($role->hasRole(RoleInterface::ROLE_WEBMASTER));

        $role = new Role(RoleInterface::ROLE_ADMIN);
        self::assertFalse($role->hasRole(RoleInterface::ROLE_USER));
        self::assertTrue($role->hasRole(RoleInterface::ROLE_ADMIN));
        self::assertFalse($role->hasRole(RoleInterface::ROLE_WEBMASTER));

        $role = new Role(RoleInterface::ROLE_WEBMASTER);
        self::assertFalse($role->hasRole(RoleInterface::ROLE_USER));
        self::assertFalse($role->hasRole(RoleInterface::ROLE_ADMIN));
        self::assertTrue($role->hasRole(RoleInterface::ROLE_WEBMASTER));
    }

    public function testIsAdmin(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertFalse($role->isAdmin());

        $role = new Role(RoleInterface::ROLE_ADMIN);
        self::assertTrue($role->isAdmin());

        $role = new Role(RoleInterface::ROLE_WEBMASTER);
        self::assertTrue($role->isAdmin());
    }

    public function testIsWebmaster(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertFalse($role->isWebmaster());

        $role = new Role(RoleInterface::ROLE_ADMIN);
        self::assertFalse($role->isWebmaster());

        $role = new Role(RoleInterface::ROLE_WEBMASTER);
        self::assertTrue($role->isWebmaster());
    }

    public function testSetName(): void
    {
        $role = new Role(RoleInterface::ROLE_ADMIN);
        $role->setName('My Name');
        self::assertSame('My Name', $role->getName());
    }

    public function testSetRole(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setRole(RoleInterface::ROLE_WEBMASTER);
        self::assertTrue($role->isWebmaster());
    }

    public function testToString(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertSame(RoleInterface::ROLE_USER, (string) $role);
    }

    public function testTranslate(): void
    {
        $translator = new IdentityTranslator();
        $role = new Role(RoleInterface::ROLE_USER);
        $actual = $role->trans($translator);
        self::assertSame('user.roles.user', $actual);
    }
}
