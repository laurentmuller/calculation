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
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(RoleBuilderService::class)]
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
        self::assertEqualsCanonicalizing($permission, $role->getCalculationPermission());
        self::assertEqualsCanonicalizing($permission, $role->getCalculationStatePermission());
        self::assertEqualsCanonicalizing($permission, $role->getCategoryPermission());
        self::assertEqualsCanonicalizing($permission, $role->getCustomerPermission());
        self::assertEqualsCanonicalizing($permission, $role->getGlobalMarginPermission());
        self::assertEqualsCanonicalizing($permission, $role->getGroupPermission());
        self::assertEqualsCanonicalizing($permission, $role->getLogPermission());
        self::assertEqualsCanonicalizing($permission, $role->getProductPermission());
        self::assertEqualsCanonicalizing($permission, $role->getTaskPermission());
        self::assertEqualsCanonicalizing($permission, $role->getUserPermission());
    }

    public function testRoleDisabled(): void
    {
        $permission = EntityPermission::getNonePermission();

        $role = $this->service->getRoleDisabled();
        self::assertTrue($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_USER, $role->getName());
        self::assertEqualsCanonicalizing($permission, $role->getCalculationPermission());
        self::assertEqualsCanonicalizing($permission, $role->getCalculationStatePermission());
        self::assertEqualsCanonicalizing($permission, $role->getCategoryPermission());
        self::assertEqualsCanonicalizing($permission, $role->getCustomerPermission());
        self::assertEqualsCanonicalizing($permission, $role->getGlobalMarginPermission());
        self::assertEqualsCanonicalizing($permission, $role->getGroupPermission());
        self::assertEqualsCanonicalizing($permission, $role->getLogPermission());
        self::assertEqualsCanonicalizing($permission, $role->getProductPermission());
        self::assertEqualsCanonicalizing($permission, $role->getTaskPermission());
        self::assertEqualsCanonicalizing($permission, $role->getUserPermission());
    }

    public function testRoleSuperAdmin(): void
    {
        $permission = EntityPermission::getAllPermission();

        $role = $this->service->getRoleSuperAdmin();
        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_SUPER_ADMIN, $role->getName());
        self::assertEqualsCanonicalizing($permission, $role->getCalculationPermission());
        self::assertEqualsCanonicalizing($permission, $role->getCalculationStatePermission());
        self::assertEqualsCanonicalizing($permission, $role->getCategoryPermission());
        self::assertEqualsCanonicalizing($permission, $role->getCustomerPermission());
        self::assertEqualsCanonicalizing($permission, $role->getGlobalMarginPermission());
        self::assertEqualsCanonicalizing($permission, $role->getGroupPermission());
        self::assertEqualsCanonicalizing($permission, $role->getLogPermission());
        self::assertEqualsCanonicalizing($permission, $role->getProductPermission());
        self::assertEqualsCanonicalizing($permission, $role->getTaskPermission());
        self::assertEqualsCanonicalizing($permission, $role->getUserPermission());
    }

    public function testRoleUser(): void
    {
        $all = EntityPermission::getAllPermission();
        $none = EntityPermission::getNonePermission();
        $default = EntityPermission::getDefaultPermission();

        $role = $this->service->getRoleUser();
        self::assertFalse($role->isOverwrite());
        self::assertSame(RoleInterface::ROLE_USER, $role->getName());
        self::assertEqualsCanonicalizing($all, $role->getCalculationPermission());
        self::assertEqualsCanonicalizing($default, $role->getCalculationStatePermission());
        self::assertEqualsCanonicalizing($default, $role->getCategoryPermission());
        self::assertEqualsCanonicalizing($default, $role->getCustomerPermission());
        self::assertEqualsCanonicalizing($default, $role->getGlobalMarginPermission());
        self::assertEqualsCanonicalizing($default, $role->getGroupPermission());
        self::assertEqualsCanonicalizing($none, $role->getLogPermission());
        self::assertEqualsCanonicalizing($default, $role->getProductPermission());
        self::assertEqualsCanonicalizing($default, $role->getTaskPermission());
        self::assertEqualsCanonicalizing($none, $role->getUserPermission());
    }
}
