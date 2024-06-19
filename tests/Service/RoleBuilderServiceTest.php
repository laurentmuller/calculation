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
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\RoleBuilderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoleBuilderService::class)]
class RoleBuilderServiceTest extends TestCase
{
    private RoleBuilderService $service;

    protected function setUp(): void
    {
        $this->service = new RoleBuilderService();
    }

    public function testGetRoleAdmin(): void
    {
        $user = new User();
        $user->setRole(RoleInterface::ROLE_ADMIN);
        $role = $this->service->getRole($user);
        self::assertRoleAdmin($role);
    }

    public function testGetRoleDisabled(): void
    {
        $user = new User();
        $user->setEnabled(false);
        $role = $this->service->getRole($user);
        self::assertRoleDisabled($role);
    }

    public function testGetRoleSuperAdmin(): void
    {
        $user = new User();
        $user->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        $role = $this->service->getRole($user);
        self::assertRoleSuperAdmin($role);
    }

    public function testGetRoleUser(): void
    {
        $user = new User();
        $user->setRole(RoleInterface::ROLE_USER);
        $role = $this->service->getRole($user);
        self::assertRoleUser($role);
    }

    public function testRoleAdmin(): void
    {
        $role = $this->service->getRoleAdmin();
        self::assertRoleAdmin($role);
    }

    public function testRoleDisabled(): void
    {
        $role = $this->service->getRoleDisabled();
        self::assertRoleDisabled($role);
    }

    public function testRoleSuperAdmin(): void
    {
        $role = $this->service->getRoleSuperAdmin();
        self::assertRoleSuperAdmin($role);
    }

    public function testRoleUser(): void
    {
        $role = $this->service->getRoleUser();
        self::assertRoleUser($role);
    }

    protected static function assertRoleAdmin(Role $role): void
    {
        $permission = EntityPermission::getAllPermission();

        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_ADMIN, $role->getName());
        self::assertEqualsCanonicalizing($permission, $role->CalculationRights);
        self::assertEqualsCanonicalizing($permission, $role->CalculationStateRights);
        self::assertEqualsCanonicalizing($permission, $role->CategoryRights);
        self::assertEqualsCanonicalizing($permission, $role->CustomerRights);
        self::assertEqualsCanonicalizing($permission, $role->GlobalMarginRights);
        self::assertEqualsCanonicalizing($permission, $role->GroupRights);
        self::assertEqualsCanonicalizing($permission, $role->LogRights);
        self::assertEqualsCanonicalizing($permission, $role->ProductRights);
        self::assertEqualsCanonicalizing($permission, $role->TaskRights);
        self::assertEqualsCanonicalizing($permission, $role->UserRights);
    }

    protected static function assertRoleDisabled(Role $role): void
    {
        $permission = EntityPermission::getNonePermission();

        self::assertTrue($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_USER, $role->getName());
        self::assertEqualsCanonicalizing($permission, $role->CalculationRights);
        self::assertEqualsCanonicalizing($permission, $role->CalculationStateRights);
        self::assertEqualsCanonicalizing($permission, $role->GroupRights);
        self::assertEqualsCanonicalizing($permission, $role->CategoryRights);
        self::assertEqualsCanonicalizing($permission, $role->ProductRights);
        self::assertEqualsCanonicalizing($permission, $role->TaskRights);
        self::assertEqualsCanonicalizing($permission, $role->GlobalMarginRights);
        self::assertEqualsCanonicalizing($permission, $role->UserRights);
        self::assertEqualsCanonicalizing($permission, $role->LogRights);
        self::assertEqualsCanonicalizing($permission, $role->CustomerRights);
    }

    protected static function assertRoleSuperAdmin(Role $role): void
    {
        $permission = EntityPermission::getAllPermission();

        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_SUPER_ADMIN, $role->getName());
        self::assertEqualsCanonicalizing($permission, $role->CalculationRights);
        self::assertEqualsCanonicalizing($permission, $role->CalculationStateRights);
        self::assertEqualsCanonicalizing($permission, $role->GroupRights);
        self::assertEqualsCanonicalizing($permission, $role->CategoryRights);
        self::assertEqualsCanonicalizing($permission, $role->ProductRights);
        self::assertEqualsCanonicalizing($permission, $role->TaskRights);
        self::assertEqualsCanonicalizing($permission, $role->GlobalMarginRights);
        self::assertEqualsCanonicalizing($permission, $role->UserRights);
        self::assertEqualsCanonicalizing($permission, $role->LogRights);
        self::assertEqualsCanonicalizing($permission, $role->CustomerRights);
    }

    protected static function assertRoleUser(Role $role): void
    {
        $all = EntityPermission::getAllPermission();
        $none = EntityPermission::getNonePermission();
        $default = EntityPermission::getDefaultPermission();

        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_USER, $role->getName());
        self::assertEqualsCanonicalizing($all, $role->CalculationRights);
        self::assertEqualsCanonicalizing($default, $role->CalculationStateRights);
        self::assertEqualsCanonicalizing($default, $role->GroupRights);
        self::assertEqualsCanonicalizing($default, $role->CategoryRights);
        self::assertEqualsCanonicalizing($default, $role->ProductRights);
        self::assertEqualsCanonicalizing($default, $role->TaskRights);
        self::assertEqualsCanonicalizing($default, $role->GlobalMarginRights);
        self::assertEqualsCanonicalizing($none, $role->UserRights);
        self::assertEqualsCanonicalizing($none, $role->LogRights);
        self::assertEqualsCanonicalizing($default, $role->CustomerRights);
    }
}
