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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(RoleTranslatorTrait::class)]
class RoleTranslatorTraitTest extends TestCase
{
    use RoleTranslatorTrait;

    private ?TranslatorInterface $translator = null;

    public static function getRoleIcons(): \Iterator
    {
        yield [RoleInterface::ROLE_USER, 'fa-solid fa-user'];
        yield [RoleInterface::ROLE_ADMIN, 'fa-solid fa-user-shield'];
        yield [RoleInterface::ROLE_SUPER_ADMIN, 'fa-solid fa-user-gear'];
        yield [new Role(RoleInterface::ROLE_USER), 'fa-solid fa-user'];
        yield [new Role(RoleInterface::ROLE_ADMIN), 'fa-solid fa-user-shield'];
        yield [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'fa-solid fa-user-gear'];
    }

    public static function getTranslateRoles(): \Iterator
    {
        yield [RoleInterface::ROLE_USER, 'user.roles.user'];
        yield [RoleInterface::ROLE_ADMIN, 'user.roles.admin'];
        yield [RoleInterface::ROLE_SUPER_ADMIN, 'user.roles.super_admin'];
        yield [new Role(RoleInterface::ROLE_USER), 'user.roles.user'];
        yield [new Role(RoleInterface::ROLE_ADMIN), 'user.roles.admin'];
        yield [new Role(RoleInterface::ROLE_SUPER_ADMIN), 'user.roles.super_admin'];
    }

    /**
     * @throws Exception
     */
    public function getTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->createTranslator();
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
     *
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTranslateRoles')]
    public function testTranslateRole(RoleInterface|string $role, string $expected): void
    {
        $this->translator = $this->createTranslator($expected);
        $actual = $this->translateRole($role);
        self::assertSame($actual, $expected);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(string $message = ''): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturn($message);

        return $translator;
    }
}
