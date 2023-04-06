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

namespace App\Tests\Enums;

use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use Elao\Enum\FlagBag;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityPermission::class)]
class EntityPermissionTest extends TestCase
{
    private ?TranslatorInterface $translator = null;

    /**
     * @return array<array{EntityPermission, string}>
     */
    public static function getLabel(): array
    {
        return [
            [EntityPermission::ADD, 'rights.add'],
            [EntityPermission::DELETE, 'rights.delete'],
            [EntityPermission::EDIT, 'rights.edit'],
            [EntityPermission::EXPORT, 'rights.export'],
            [EntityPermission::LIST, 'rights.list'],
            [EntityPermission::SHOW, 'rights.show'],
        ];
    }

    public static function getMatchName(): array
    {
        return [
            [EntityPermission::ADD, 'add'],
            [EntityPermission::ADD, 'AdD'],
            [EntityPermission::ADD, 'Add'],
            [EntityPermission::ADD, 'ADD'],
            [EntityPermission::DELETE, 'DELETE'],
            [EntityPermission::EDIT, 'EDIT'],
            [EntityPermission::EXPORT, 'EXPORT'],
            [EntityPermission::LIST, 'LIST'],
            [EntityPermission::SHOW, 'SHOW'],

            [EntityPermission::ADD, '', false],
            [EntityPermission::ADD, 'FAKE', false],
        ];
    }

    public static function getTryFindValue(): array
    {
        return [
            ['add', 1],
            ['delete', 2],
            ['edit', 4],
            ['export', 8],
            ['list', 16],
            ['show', 32],

            ['fake', -1],
            ['fake', 1, 1],
        ];
    }

    public static function getTryFromName(): array
    {
        return [
            [EntityPermission::ADD, 'add'],
            [EntityPermission::ADD, 'AdD'],
            [EntityPermission::ADD, 'Add'],
            [EntityPermission::ADD, 'ADD'],
            [EntityPermission::DELETE, 'DELETE'],
            [EntityPermission::EDIT, 'EDIT'],
            [EntityPermission::EXPORT, 'EXPORT'],
            [EntityPermission::LIST, 'LIST'],
            [EntityPermission::SHOW, 'SHOW'],

            [null, ''],
            [null, 'FAKE'],
        ];
    }

    public static function getValue(): array
    {
        return [
            [EntityPermission::ADD, 1],
            [EntityPermission::DELETE, 2],
            [EntityPermission::EDIT, 4],
            [EntityPermission::EXPORT, 8],
            [EntityPermission::LIST, 16],
            [EntityPermission::SHOW, 32],
        ];
    }

    public function testBits(): void
    {
        $expected = [1, 2, 4, 8, 16, 32];
        $permissions = FlagBag::fromAll(EntityPermission::class);
        $bits = $permissions->getBits();
        self::assertSame($expected, $bits);
    }

    public function testConstants(): void
    {
        $cases = EntityPermission::cases();
        $constants = EntityPermission::constants();
        self::assertSameSize($cases, $constants);

        foreach ($constants as $key => $value) {
            self::assertStringStartsWith('ATTRIBUTE_', $key);
            self::assertNotNull(EntityPermission::tryFromName($value));
        }
    }

    public function testCount(): void
    {
        $permissions = FlagBag::fromAll(EntityPermission::class);
        $bits = $permissions->getBits();
        self::assertCount(6, $bits);

        $flags = $permissions->getFlags();
        self::assertCount(6, $flags);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testLabel(EntityPermission $permission, string $expected): void
    {
        $label = $permission->getReadable();
        self::assertSame($expected, $label);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMatchName')]
    public function testMatchName(EntityPermission $permission, string $name, bool $expected = true): void
    {
        $result = $permission->matchName($name);
        self::assertSame($expected, $result);
    }

    public function testSorted(): void
    {
        $expected = [
            EntityPermission::LIST,
            EntityPermission::SHOW,
            EntityPermission::ADD,
            EntityPermission::EDIT,
            EntityPermission::DELETE,
            EntityPermission::EXPORT,
        ];
        $sorted = EntityPermission::sorted();
        self::assertSame($expected, $sorted);
    }

    public function testSum(): void
    {
        $permissions = FlagBag::fromAll(EntityPermission::class);
        self::assertSame(63, $permissions->getValue());
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(EntityPermission $permission, string $expected): void
    {
        $translator = $this->createTranslator();
        $label = $permission->trans($translator);
        self::assertSame($expected, $label);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFindValue')]
    public function testTryFindValue(string $name, int $expected, int $default = RoleInterface::INVALID_VALUE): void
    {
        $result = EntityPermission::tryFindValue($name, $default);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFromName')]
    public function testTryFromName(mixed $expected, string $value): void
    {
        $result = EntityPermission::tryFromName($value);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValue')]
    public function testValue(EntityPermission $permission, int $expected): void
    {
        $value = $permission->value;
        self::assertSame($expected, $value);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->createMock(TranslatorInterface::class);
            $this->translator->method('trans')
                ->willReturnArgument(0);
        }

        return $this->translator;
    }
}
