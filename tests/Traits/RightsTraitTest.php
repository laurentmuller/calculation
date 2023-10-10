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

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Traits\RightsTrait;
use Elao\Enum\FlagBag;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(RightsTrait::class)]
class RightsTraitTest extends TestCase
{
    use RightsTrait;

    protected function setUp(): void
    {
        $this->rights = null;
    }

    public static function getAttributes(): \Generator
    {
        $values = \array_values(EntityPermission::constants());
        foreach ($values as $value) {
            yield [$value];
        }
    }

    public static function getEntities(): \Generator
    {
        $values = \array_values(EntityName::constants());
        foreach ($values as $value) {
            yield [$value];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getEntities')]
    public function testGetAdd(string $entity): void
    {
        $this->checkAttribute($entity, 'ADD');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getEntities')]
    public function testGetDelete(string $entity): void
    {
        $this->checkAttribute($entity, 'DELETE');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getEntities')]
    public function testGetEdit(string $entity): void
    {
        $this->checkAttribute($entity, 'EDIT');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getEntities')]
    public function testGetEmpty(string $entity): void
    {
        /** @psalm-var FlagBag<EntityPermission> $entity */
        $entity = $this->$entity;
        self::assertSame(0, $entity->getValue());
    }

    public function testInvalidAttribute(): void
    {
        $attribute = $this->getAttribute('UnknownAttribute');
        self::assertSame(RoleInterface::INVALID_VALUE, $attribute);
    }

    public function testIsNotSet(): void
    {
        $className = 'UnknownClass';
        self::assertFalse($this->__isset($className));
        $value = $this->__get($className);
        self::assertNull($value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getEntities')]
    public function testIsSet(string $entity): void
    {
        self::assertTrue($this->__isset($entity));
        /** @psalm-var FlagBag<EntityPermission> $entity */
        $entity = $this->$entity;
        self::assertInstanceOf(FlagBag::class, $entity);
        self::assertSame(0, $entity->getValue());
    }

    private function checkAttribute(string $entity, string $key): void
    {
        $attribute = $this->getAttribute($key);
        $rights = new FlagBag(EntityPermission::class, $attribute);
        $this->$entity = $rights;
        /** @psalm-var FlagBag<EntityPermission> $value */
        $value = $this->$entity;
        self::assertSame($rights->getValue(), $value->getValue());
    }

    private function getAttribute(string $key): int
    {
        $permission = EntityPermission::tryFromName($key);

        return $permission instanceof EntityPermission ? $permission->value : RoleInterface::INVALID_VALUE;
    }
}
