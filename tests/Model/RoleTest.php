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

class RoleTest extends TestCase
{
    public function testGetName(): void
    {
        $role = new Role('My Role');
        self::assertSame('My Role', $role->getName());
        $role = new Role('My Role', 'My Name');
        self::assertSame('My Name', $role->getName());
    }

    public function testGetRole(): void
    {
        $role = new Role('My Role');
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
        self::assertFalse($role->hasRole(RoleInterface::ROLE_SUPER_ADMIN));

        $role = new Role(RoleInterface::ROLE_ADMIN);
        self::assertFalse($role->hasRole(RoleInterface::ROLE_USER));
        self::assertTrue($role->hasRole(RoleInterface::ROLE_ADMIN));
        self::assertFalse($role->hasRole(RoleInterface::ROLE_SUPER_ADMIN));

        $role = new Role(RoleInterface::ROLE_SUPER_ADMIN);
        self::assertFalse($role->hasRole(RoleInterface::ROLE_USER));
        self::assertFalse($role->hasRole(RoleInterface::ROLE_ADMIN));
        self::assertTrue($role->hasRole(RoleInterface::ROLE_SUPER_ADMIN));
    }

    public function testIsAdmin(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertFalse($role->isAdmin());

        $role = new Role(RoleInterface::ROLE_ADMIN);
        self::assertTrue($role->isAdmin());

        $role = new Role(RoleInterface::ROLE_SUPER_ADMIN);
        self::assertTrue($role->isAdmin());
    }

    public function testIsSuperAdmin(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        self::assertFalse($role->isSuperAdmin());

        $role = new Role(RoleInterface::ROLE_ADMIN);
        self::assertFalse($role->isSuperAdmin());

        $role = new Role(RoleInterface::ROLE_SUPER_ADMIN);
        self::assertTrue($role->isSuperAdmin());
    }

    public function testSetName(): void
    {
        $role = new Role('My Role');
        self::assertSame('My Role', $role->getName());
        $role->setName('My Name');
        self::assertSame('My Name', $role->getName());
    }

    public function testSetRole(): void
    {
        $role = new Role('My Role');
        $role->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        self::assertTrue($role->isSuperAdmin());
    }

    public function testToString(): void
    {
        $role = new Role('');
        self::assertSame('', $role->__toString());
        $role = new Role('My Role');
        self::assertSame('My Role', $role->__toString());
    }
}
