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

#[\PHPUnit\Framework\Attributes\CoversClass(RightsTrait::class)]
class RightsTraitTest extends TestCase
{
    use RightsTrait;

    protected function setUp(): void
    {
        $this->rights = null;
    }

    public static function getRightsFields(): \Generator
    {
        $values = EntityName::cases();
        foreach ($values as $value) {
            yield [$value->getRightsField()];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRightsFields')]
    public function testGetAdd(string $field): void
    {
        $this->checkAttribute($field, 'ADD');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRightsFields')]
    public function testGetDelete(string $field): void
    {
        $this->checkAttribute($field, 'DELETE');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRightsFields')]
    public function testGetEdit(string $field): void
    {
        $this->checkAttribute($field, 'EDIT');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRightsFields')]
    public function testGetEmpty(string $field): void
    {
        $permission = $this->__get($field);
        self::assertNotNull($permission);
        self::assertSame(0, $permission->getValue());
    }

    public function testInvalidAttribute(): void
    {
        $attribute = $this->getAttribute('UnknownAttribute');
        self::assertSame(EntityPermission::INVALID_VALUE, $attribute);
    }

    public function testPermissionEmpty(): void
    {
        $permission = new FlagBag(EntityPermission::class);
        $this->CalculationRights = $permission;
        $actual = $this->CalculationRights;
        self::assertSame($permission->getValue(), $actual->getValue());
    }

    public function testPermissionShow(): void
    {
        $expected = EntityPermission::SHOW->value;
        $permission = new FlagBag(EntityPermission::class, $expected);
        $this->CalculationRights = $permission;
        $actual = $this->CalculationRights;
        self::assertSame($expected, $actual->getValue());
    }

    private function checkAttribute(string $field, string $key): void
    {
        $attribute = $this->getAttribute($key);
        $rights = new FlagBag(EntityPermission::class, $attribute);
        $this->$field = $rights;
        /** @psalm-var FlagBag<EntityPermission> $value */
        $value = $this->$field;
        self::assertSame($rights->getValue(), $value->getValue());
    }

    private function getAttribute(string $key): int
    {
        $permission = EntityPermission::tryFromName($key);

        return $permission instanceof EntityPermission ? $permission->value : EntityPermission::INVALID_VALUE;
    }
}
