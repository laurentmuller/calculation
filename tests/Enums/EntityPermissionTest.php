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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityPermission::class)]
class EntityPermissionTest extends TestCase
{
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
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

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(EntityPermission $permission, string $expected): void
    {
        $translator = $this->createTranslator();
        $actual = $permission->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFromName')]
    public function testTryFromName(mixed $expected, string $value): void
    {
        $actual = EntityPermission::tryFromName($value);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValue')]
    public function testValue(EntityPermission $permission, int $expected): void
    {
        $actual = $permission->value;
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }

    /**
     * @psalm-return FlagBag<EntityPermission>
     *
     * @psalm-suppress InvalidArgument
     */
    private function fromAll(): FlagBag
    {
        /** @psalm-var FlagBag<EntityPermission> $flagBag */
        $flagBag = FlagBag::fromAll(EntityPermission::class);

        return $flagBag;
    }
}
