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

namespace App\Tests\Service;

use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\RoleHierarchyService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleHierarchyServiceTest extends TestCase
{
    public function testGetRoleNamesWithHierarchy(): void
    {
        $user = new User();
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')
            ->willReturn([RoleInterface::ROLE_ADMIN]);

        $service = new RoleHierarchyService($hierarchy);
        $actual = $service->getReachableRoleNames($user);
        self::assertCount(1, $actual);
        self::assertSame([RoleInterface::ROLE_ADMIN], $actual);
    }

    public function testGetRoleNamesWithNull(): void
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $service = new RoleHierarchyService($hierarchy);
        $actual = $service->getRoleNames(null);
        self::assertEmpty($actual);
    }

    public function testGetRoleNamesWithRole(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setRole(RoleInterface::ROLE_ADMIN);
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $service = new RoleHierarchyService($hierarchy);
        $actual = $service->getRoleNames($role);
        self::assertCount(1, $actual);
        self::assertSame([RoleInterface::ROLE_ADMIN], $actual);
    }

    public function testGetRoleNamesWithUser(): void
    {
        $user = new User();
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $service = new RoleHierarchyService($hierarchy);
        $actual = $service->getRoleNames($user);
        self::assertCount(1, $actual);
        self::assertSame([RoleInterface::ROLE_USER], $actual);
    }

    public function testHasRoleWithNull(): void
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $service = new RoleHierarchyService($hierarchy);
        $actual = $service->hasRole(null, RoleInterface::ROLE_USER);
        self::assertFalse($actual);
    }
}
