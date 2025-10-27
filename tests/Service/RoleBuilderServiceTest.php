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
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\RoleBuilderService;
use App\Tests\FlagBagTestCase;

final class RoleBuilderServiceTest extends FlagBagTestCase
{
    private RoleBuilderService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new RoleBuilderService();
    }

    public function testGetRoleAdmin(): void
    {
        $user = new User();
        $user->setRole(RoleInterface::ROLE_ADMIN);
        $role = $this->service->getRole($user);
        $this->assertRoleAdmin($role);
    }

    public function testGetRoleDisabled(): void
    {
        $user = new User();
        $user->setEnabled(false);
        $role = $this->service->getRole($user);
        $this->assertRoleDisabled($role);
    }

    public function testGetRoleSuperAdmin(): void
    {
        $user = new User();
        $user->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        $role = $this->service->getRole($user);
        $this->assertRoleSuperAdmin($role);
    }

    public function testGetRoleUser(): void
    {
        $user = new User();
        $user->setRole(RoleInterface::ROLE_USER);
        $role = $this->service->getRole($user);
        $this->assertRoleUser($role);
    }

    public function testRoleAdmin(): void
    {
        $role = $this->service->getRoleAdmin();
        $this->assertRoleAdmin($role);
    }

    public function testRoleDisabled(): void
    {
        $role = $this->service->getRoleDisabled();
        $this->assertRoleDisabled($role);
    }

    public function testRoleSuperAdmin(): void
    {
        $role = $this->service->getRoleSuperAdmin();
        $this->assertRoleSuperAdmin($role);
    }

    public function testRoleUser(): void
    {
        $role = $this->service->getRoleUser();
        $this->assertRoleUser($role);
    }

    private function assertRoleAdmin(Role $role): void
    {
        $entities = EntityName::cases();
        $permission = EntityPermission::getAllPermission();

        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_ADMIN, $role->getName());

        foreach ($entities as $entity) {
            self::assertSameFlagBag($permission, $role->getPermission($entity));
        }
    }

    private function assertRoleDisabled(Role $role): void
    {
        $entities = EntityName::cases();
        $permission = EntityPermission::getNonePermission();

        self::assertTrue($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_USER, $role->getName());

        foreach ($entities as $entity) {
            self::assertSameFlagBag($permission, $role->getPermission($entity));
        }
    }

    private function assertRoleSuperAdmin(Role $role): void
    {
        $entities = EntityName::cases();
        $permission = EntityPermission::getAllPermission();

        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_SUPER_ADMIN, $role->getName());

        foreach ($entities as $entity) {
            self::assertSameFlagBag($permission, $role->getPermission($entity));
        }
    }

    private function assertRoleUser(Role $role): void
    {
        $all = EntityPermission::getAllPermission();
        $none = EntityPermission::getNonePermission();
        $default = EntityPermission::getDefaultPermission();

        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_USER, $role->getName());

        self::assertSameFlagBag($all, $role->getPermission(EntityName::CALCULATION));

        self::assertSameFlagBag($default, $role->getPermission(EntityName::CALCULATION_STATE));
        self::assertSameFlagBag($default, $role->getPermission(EntityName::GROUP));
        self::assertSameFlagBag($default, $role->getPermission(EntityName::CATEGORY));
        self::assertSameFlagBag($default, $role->getPermission(EntityName::PRODUCT));
        self::assertSameFlagBag($default, $role->getPermission(EntityName::TASK));
        self::assertSameFlagBag($default, $role->getPermission(EntityName::CUSTOMER));
        self::assertSameFlagBag($default, $role->getPermission(EntityName::GLOBAL_MARGIN));

        self::assertSameFlagBag($none, $role->getPermission(EntityName::USER));
        self::assertSameFlagBag($none, $role->getPermission(EntityName::LOG));
    }
}
