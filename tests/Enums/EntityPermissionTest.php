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
use Elao\Enum\FlagBag;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link EntityPermission} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityPermissionTest extends TestCase
{
    public function getTryFromName(): array
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

    public function testBits(): void
    {
        $expected = [1, 2, 4, 8, 16, 32];
        $permissions = self::fromAll();
        $bits = $permissions->getBits();
        self::assertEquals($expected, $bits);
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
        $permissions = self::fromAll();
        $bits = $permissions->getBits();
        self::assertCount(6, $bits);

        $flags = $permissions->getFlags();
        self::assertCount(6, $flags);
    }

    public function testLabel(): void
    {
        self::assertEquals('rights.add', EntityPermission::ADD->getReadable());
        self::assertEquals('rights.delete', EntityPermission::DELETE->getReadable());
        self::assertEquals('rights.edit', EntityPermission::EDIT->getReadable());
        self::assertEquals('rights.export', EntityPermission::EXPORT->getReadable());
        self::assertEquals('rights.list', EntityPermission::LIST->getReadable());
        self::assertEquals('rights.show', EntityPermission::SHOW->getReadable());
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
        self::assertEquals($expected, $sorted);
    }

    public function testSum(): void
    {
        $permissions = self::fromAll();
        self::assertEquals(63, $permissions->getValue());
    }

    /**
     * @dataProvider getTryFromName
     */
    public function testTryFromName(mixed $expected, string $value): void
    {
        $result = EntityPermission::tryFromName($value);
        self::assertEquals($expected, $result);
    }

    public function testValue(): void
    {
        self::assertEquals(1, EntityPermission::ADD->value);
        self::assertEquals(2, EntityPermission::DELETE->value);
        self::assertEquals(4, EntityPermission::EDIT->value);
        self::assertEquals(8, EntityPermission::EXPORT->value);
        self::assertEquals(16, EntityPermission::LIST->value);
        self::assertEquals(32, EntityPermission::SHOW->value);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    private static function fromAll(): FlagBag
    {
        return FlagBag::fromAll(EntityPermission::class);
    }
}
