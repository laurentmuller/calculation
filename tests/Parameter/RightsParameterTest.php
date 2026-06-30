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

namespace App\Tests\Parameter;

use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Parameter\RightsParameter;
use App\Service\RoleBuilderService;

/**
 * @extends ParameterTestCase<RightsParameter>
 */
final class RightsParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['adminRights', 'admin_rights'];
        yield ['userRights', 'user_rights'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['adminRights', null];
        yield ['userRights', null];
    }

    public function testDefaultValue(): void
    {
        self::assertNull($this->parameter->getAdminRights());
        self::assertNull($this->parameter->getUserRights());

        $service = new RoleBuilderService();
        $default = $service->getAdminRole()
            ->getRights();
        $this->parameter->setAdminRights($default);
        self::assertNull($this->parameter->getAdminRights());

        $default = $service->getUserRole()
            ->getRights();
        $this->parameter->setUserRights($default);
        self::assertNull($this->parameter->getUserRights());

        self::assertSame('parameter_rights', $this->parameter::getCacheKey());
    }

    public function testGetAdminRole(): void
    {
        $role = $this->parameter->getAdminRole();
        self::assertSame('ROLE_ADMIN', $role->getName());
    }

    public function testGetUserRole(): void
    {
        $role = $this->parameter->getUserRole();
        self::assertSame('ROLE_USER', $role->getName());
    }

    public function testSetRightsFromRoleAdmin(): void
    {
        $rights = 1;
        $role = new Role(RoleInterface::ROLE_ADMIN);
        $role->setRights($rights);
        $this->parameter->setRightsFromRole($role);
        self::assertNull($this->parameter->getUserRights());
        self::assertSame($rights, $this->parameter->getAdminRights());
    }

    public function testSetRightsFromRoleInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role: "ROLE_SUPER_ADMIN".');
        $role = new Role(RoleInterface::ROLE_SUPER_ADMIN);
        $this->parameter->setRightsFromRole($role);
    }

    public function testSetRightsFromRoleUser(): void
    {
        $rights = 1;
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setRights($rights);
        $this->parameter->setRightsFromRole($role);
        self::assertSame($rights, $this->parameter->getUserRights());
        self::assertNull($this->parameter->getAdminRights());
    }

    public function testSetValue(): void
    {
        $rights = 1;
        $this->parameter->setAdminRights($rights);
        self::assertSame($rights, $this->parameter->getAdminRights());
        self::assertSame($rights, $this->parameter->getAdminRole()->getRights());
        $this->parameter->setUserRights($rights);
        self::assertSame($rights, $this->parameter->getUserRights());
        self::assertSame($rights, $this->parameter->getUserRole()->getRights());
    }

    #[\Override]
    protected function createParameter(): RightsParameter
    {
        return new RightsParameter();
    }
}
