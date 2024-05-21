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

use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
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

    public function testRoleAdmin(): void
    {
        $permission = EntityPermission::getAllPermission();

        $role = $this->service->getRoleAdmin();
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

    public function testRoleDisabled(): void
    {
        $permission = EntityPermission::getNonePermission();

        $role = $this->service->getRoleDisabled();
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

    public function testRoleSuperAdmin(): void
    {
        $permission = EntityPermission::getAllPermission();

        $role = $this->service->getRoleSuperAdmin();
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

    public function testRoleUser(): void
    {
        $all = EntityPermission::getAllPermission();
        $none = EntityPermission::getNonePermission();
        $default = EntityPermission::getDefaultPermission();

        $role = $this->service->getRoleUser();
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
