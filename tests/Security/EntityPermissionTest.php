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

namespace App\Tests\Security;

use App\Enums\EntityPermission;
use Elao\Enum\FlagBag;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link EntityPermission} enumeration.
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

    public function getValues(): array
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
        self::assertEquals($expected, EntityPermission::sorted());
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
        self::assertSame($expected, $result);
    }

    /**
     * @dataProvider getValues
     */
    public function testValue(EntityPermission $p, int $expected): void
    {
        self::assertEquals($expected, $p->value);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    private static function fromAll(): FlagBag
    {
        return FlagBag::fromAll(EntityPermission::class);
    }
}
