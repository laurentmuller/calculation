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
use App\Service\RoleService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final class RoleServiceTest extends TestCase
{
    use TranslatorMockTrait;

    public static function getRoleIcons(): \Generator
    {
        yield [RoleInterface::ROLE_USER, 'fa-solid fa-user'];
        yield [RoleInterface::ROLE_ADMIN, 'fa-solid fa-user-shield'];
        yield [RoleInterface::ROLE_SUPER_ADMIN, 'fa-solid fa-user-gear'];

        yield [new Role(RoleInterface::ROLE_USER), 'fa-solid fa-user'];
        yield [new Role(RoleInterface::ROLE_ADMIN), 'fa-solid fa-user-shield'];
        yield [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'fa-solid fa-user-gear'];

        yield [self::createUser(null), 'fa-solid fa-user'];
        yield [self::createUser(RoleInterface::ROLE_USER), 'fa-solid fa-user'];
        yield [self::createUser(RoleInterface::ROLE_ADMIN), 'fa-solid fa-user-shield'];
        yield [self::createUser(RoleInterface::ROLE_SUPER_ADMIN), 'fa-solid fa-user-gear'];
    }

    public static function getTranslateRoles(): \Generator
    {
        yield [RoleInterface::ROLE_USER, 'user.roles.user'];
        yield [RoleInterface::ROLE_ADMIN, 'user.roles.admin'];
        yield [RoleInterface::ROLE_SUPER_ADMIN, 'user.roles.super_admin'];

        yield [new Role(RoleInterface::ROLE_USER), 'user.roles.user'];
        yield [new Role(RoleInterface::ROLE_ADMIN), 'user.roles.admin'];
        yield [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'user.roles.super_admin'];

        yield [self::createUser(null), 'user.roles.user'];
        yield [self::createUser(RoleInterface::ROLE_USER), 'user.roles.user'];
        yield [self::createUser(RoleInterface::ROLE_ADMIN), 'user.roles.admin'];
        yield [self::createUser(RoleInterface::ROLE_SUPER_ADMIN), 'user.roles.super_admin'];
    }

    public function testGetRoleNamesWithHierarchy(): void
    {
        $user = new User();
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')
            ->willReturn([RoleInterface::ROLE_ADMIN]);
        $service = $this->createService($hierarchy);
        $actual = $service->getReachableRoleNames($user);
        self::assertCount(1, $actual);
        self::assertSame([RoleInterface::ROLE_ADMIN], $actual);
    }

    public function testGetRoleNamesWithNull(): void
    {
        $service = $this->createService();
        $actual = $service->getRoleNames(null);
        self::assertEmpty($actual);
    }

    public function testGetRoleNamesWithRole(): void
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setRole(RoleInterface::ROLE_ADMIN);
        $service = $this->createService();
        $actual = $service->getRoleNames($role);
        self::assertCount(1, $actual);
        self::assertSame([RoleInterface::ROLE_ADMIN], $actual);
    }

    public function testGetRoleNamesWithUser(): void
    {
        $user = new User();
        $service = $this->createService();
        $actual = $service->getRoleNames($user);
        self::assertCount(1, $actual);
        self::assertSame([RoleInterface::ROLE_USER], $actual);
    }

    public function testHasRoleWithNull(): void
    {
        $service = $this->createService();
        $actual = $service->hasRole(null, RoleInterface::ROLE_USER);
        self::assertFalse($actual);
    }

    /**
     * @phpstan-param RoleInterface|RoleInterface::ROLE_* $role
     */
    #[DataProvider('getRoleIcons')]
    public function testRoleIcon(RoleInterface|string $role, string $expected): void
    {
        $service = $this->createService();
        $actual = $service->getRoleIcon($role);
        self::assertSame($actual, $expected);
    }

    public function testRoleIconAndName(): void
    {
        $service = $this->createService();
        $role = new Role(RoleInterface::ROLE_USER);
        $actual = $service->getRoleIconAndName($role);
        $expected = '<i class="me-1 fa-solid fa-user"></i>user.roles.user';
        self::assertSame($expected, $actual);
    }

    public function testTranslateEnabled(): void
    {
        $service = $this->createService();
        $actual = $service->translateEnabled(true);
        self::assertSame('common.value_enabled', $actual);
        $actual = $service->translateEnabled(false);
        self::assertSame('common.value_disabled', $actual);
    }

    /**
     * @phpstan-param RoleInterface|RoleInterface::ROLE_* $role
     */
    #[DataProvider('getTranslateRoles')]
    public function testTranslateRole(RoleInterface|string $role, string $expected): void
    {
        $service = $this->createService();
        $actual = $service->translateRole($role);
        self::assertSame($actual, $expected);
    }

    private function createService(?RoleHierarchyInterface $hierarchy = null): RoleService
    {
        $hierarchy ??= $this->createMock(RoleHierarchyInterface::class);
        $translator = $this->createMockTranslator();

        return new RoleService($hierarchy, $translator);
    }

    /**
     * @phpstan-param RoleInterface::ROLE_*|null $role
     */
    private static function createUser(?string $role): User
    {
        $user = new User();
        if (\is_string($role)) {
            $user->setRole($role);
        }

        return $user;
    }
}
