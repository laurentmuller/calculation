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
use App\Tests\TranslatorMockTrait;
use Elao\Enum\FlagBag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EntityPermissionTest extends TestCase
{
    use TranslatorMockTrait;

    public static function getLabel(): \Generator
    {
        yield [EntityPermission::ADD, 'rights.add'];
        yield [EntityPermission::DELETE, 'rights.delete'];
        yield [EntityPermission::EDIT, 'rights.edit'];
        yield [EntityPermission::EXPORT, 'rights.export'];
        yield [EntityPermission::LIST, 'rights.list'];
        yield [EntityPermission::SHOW, 'rights.show'];
    }

    public static function getTryFromName(): \Generator
    {
        yield [EntityPermission::ADD, 'add'];
        yield [EntityPermission::ADD, 'AdD'];
        yield [EntityPermission::ADD, 'Add'];
        yield [EntityPermission::ADD, 'ADD'];
        yield [EntityPermission::DELETE, 'DELETE'];
        yield [EntityPermission::EDIT, 'EDIT'];
        yield [EntityPermission::EXPORT, 'EXPORT'];
        yield [EntityPermission::LIST, 'LIST'];
        yield [EntityPermission::SHOW, 'SHOW'];
        yield [null, ''];
        yield [null, 'FAKE'];
    }

    public static function getValue(): \Generator
    {
        yield [EntityPermission::ADD, 1];
        yield [EntityPermission::DELETE, 2];
        yield [EntityPermission::EDIT, 4];
        yield [EntityPermission::EXPORT, 8];
        yield [EntityPermission::LIST, 16];
        yield [EntityPermission::SHOW, 32];
    }

    public function testAllPermission(): void
    {
        $permission = EntityPermission::getAllPermission();
        $values = EntityPermission::cases();
        foreach ($values as $value) {
            self::assertTrue($permission->hasBits($value->value));
        }
    }

    public function testBits(): void
    {
        $expected = [1, 2, 4, 8, 16, 32];
        $permissions = $this->fromAll();
        $bits = $permissions->getBits();
        self::assertSame($expected, $bits);
    }

    public function testConstants(): void
    {
        $cases = EntityPermission::cases();
        $constants = EntityPermission::constants();
        self::assertSameSize($cases, $constants);

        foreach ($constants as $key => $value) {
            self::assertStringStartsWith('PERMISSION_', $key);
            self::assertNotNull(EntityPermission::tryFromName($value));
        }
    }

    public function testCount(): void
    {
        $expected = 6;
        $permissions = $this->fromAll();
        $actual = $permissions->getBits();
        self::assertCount($expected, $actual);

        $actual = $permissions->getFlags();
        self::assertCount($expected, $actual);
    }

    public function testDefaultPermission(): void
    {
        $permission = EntityPermission::getDefaultPermission();
        $trueValues = [
            EntityPermission::LIST,
            EntityPermission::EXPORT,
            EntityPermission::SHOW,
        ];
        foreach ($trueValues as $value) {
            self::assertTrue($permission->hasBits($value->value));
        }

        $falseValues = [
            EntityPermission::ADD,
            EntityPermission::DELETE,
            EntityPermission::EDIT,
        ];
        foreach ($falseValues as $value) {
            self::assertFalse($permission->hasBits($value->value));
        }
    }

    #[DataProvider('getLabel')]
    public function testLabel(EntityPermission $permission, string $expected): void
    {
        $actual = $permission->getReadable();
        self::assertSame($expected, $actual);
    }

    public function testNonePermission(): void
    {
        $permission = EntityPermission::getNonePermission();
        $values = EntityPermission::cases();
        foreach ($values as $value) {
            self::assertFalse($permission->hasBits($value->value));
        }
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
        $actual = EntityPermission::sorted();
        self::assertSame($expected, $actual);
    }

    public function testSum(): void
    {
        $expected = 63;
        $actual = $this->fromAll()->getValue();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabel')]
    public function testTranslate(EntityPermission $permission, string $expected): void
    {
        $translator = $this->createMockTranslator();
        $actual = $permission->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getTryFromName')]
    public function testTryFromName(mixed $expected, string $value): void
    {
        $actual = EntityPermission::tryFromName($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValue')]
    public function testValue(EntityPermission $permission, int $expected): void
    {
        $actual = $permission->value;
        self::assertSame($expected, $actual);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    private function fromAll(): FlagBag
    {
        return FlagBag::fromAll(EntityPermission::class);
    }
}
