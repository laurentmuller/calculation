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
use App\Traits\RoleTranslatorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(RoleTranslatorTrait::class)]
class RoleTranslatorTraitTest extends TestCase
{
    use RoleTranslatorTrait;

    private ?TranslatorInterface $translator = null;

    public static function getRoleIcons(): array
    {
        return [
            [RoleInterface::ROLE_USER, 'fa-solid fa-user'],
            [RoleInterface::ROLE_ADMIN, 'fa-solid fa-user-shield'],
            [RoleInterface::ROLE_SUPER_ADMIN, 'fa-solid fa-user-gear'],

            [new Role(RoleInterface::ROLE_USER), 'fa-solid fa-user'],
            [new Role(RoleInterface::ROLE_ADMIN), 'fa-solid fa-user-shield'],
            [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'fa-solid fa-user-gear'],
        ];
    }

    public static function getTranslateRoles(): array
    {
        return [
            [RoleInterface::ROLE_USER, 'user.roles.user'],
            [RoleInterface::ROLE_ADMIN, 'user.roles.admin'],
            [RoleInterface::ROLE_SUPER_ADMIN, 'user.roles.super_admin'],

            [new Role(RoleInterface::ROLE_USER), 'user.roles.user'],
            [new Role(RoleInterface::ROLE_ADMIN), 'user.roles.admin'],
            [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'user.roles.super_admin'],
        ];
    }

    public function getTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->createTranslator('');
        }

        return $this->translator;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRoleIcons')]
    public function testRoleIcon(RoleInterface|string $role, string $expected): void
    {
        $actual = $this->getRoleIcon($role);
        self::assertSame($actual, $expected);
    }

    /**
     * @param string|RoleInterface $role the role to translate
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTranslateRoles')]
    public function testTranslateRole(RoleInterface|string $role, string $expected): void
    {
        $this->translator = $this->createTranslator($expected);
        $actual = $this->translateRole($role);
        self::assertSame($actual, $expected);
    }

    private function createTranslator(string $message): TranslatorInterface
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturn($message);

        return $translator;
    }
}
