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
use App\Traits\RightsTrait;
use Elao\Enum\FlagBag;
use PHPUnit\Framework\TestCase;

#[\AllowDynamicProperties]
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

    public static function getFieldNames(): \Generator
    {
        $values = EntityName::cases();
        foreach ($values as $value) {
            yield [$value->getPropertyName()];
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getFieldNames')]
    public function testGetEmpty(string $field): void
    {
        /** @psalm-var FlagBag<EntityPermission> $permission */
        $permission = $this->$field();
        self::assertSame(0, $permission->getValue());
    }

    public function testInvalidAttribute(): void
    {
        $attribute = $this->getAttribute('UnknownAttribute');
        self::assertSame(EntityPermission::INVALID_VALUE, $attribute);
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

        return $permission instanceof EntityPermission ? $permission->value : EntityPermission::INVALID_VALUE;
    }
}
