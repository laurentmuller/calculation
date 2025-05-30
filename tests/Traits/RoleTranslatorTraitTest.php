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

namespace App\Tests\Traits;

use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Tests\TranslatorMockTrait;
use App\Traits\RoleTranslatorTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoleTranslatorTraitTest extends TestCase
{
    use RoleTranslatorTrait;
    use TranslatorMockTrait;

    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMockTranslator();
    }

    /**
     * @phpstan-return \Generator<int, array{Role|string, string}>
     */
    public static function getRoleIcons(): \Generator
    {
        yield [RoleInterface::ROLE_USER, 'fa-solid fa-user'];
        yield [RoleInterface::ROLE_ADMIN, 'fa-solid fa-user-shield'];
        yield [RoleInterface::ROLE_SUPER_ADMIN, 'fa-solid fa-user-gear'];
        yield [new Role(RoleInterface::ROLE_USER), 'fa-solid fa-user'];
        yield [new Role(RoleInterface::ROLE_ADMIN), 'fa-solid fa-user-shield'];
        yield [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'fa-solid fa-user-gear'];
    }

    /**
     * @phpstan-return \Generator<int, array{Role|string, string}>
     */
    public static function getTranslateRoles(): \Generator
    {
        yield [RoleInterface::ROLE_USER, 'user.roles.user'];
        yield [RoleInterface::ROLE_ADMIN, 'user.roles.admin'];
        yield [RoleInterface::ROLE_SUPER_ADMIN, 'user.roles.super_admin'];
        yield [new Role(RoleInterface::ROLE_USER), 'user.roles.user'];
        yield [new Role(RoleInterface::ROLE_ADMIN), 'user.roles.admin'];
        yield [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'user.roles.super_admin'];
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    #[DataProvider('getRoleIcons')]
    public function testRoleIcon(RoleInterface|string $role, string $expected): void
    {
        $actual = $this->getRoleIcon($role);
        self::assertSame($actual, $expected);
    }

    #[DataProvider('getTranslateRoles')]
    public function testTranslateRole(RoleInterface|string $role, string $expected): void
    {
        $this->translator = $this->createMockTranslator($expected);
        $actual = $this->translateRole($role);
        self::assertSame($actual, $expected);
    }
}
