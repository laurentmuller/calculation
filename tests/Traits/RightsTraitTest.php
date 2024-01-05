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
        $expected = 0;
        $actual = $permission->getValue();
        self::assertSame($expected, $actual);
    }

    public function testInvalidAttribute(): void
    {
        $expected = EntityPermission::INVALID_VALUE;
        $actual = $this->getAttribute('UnknownAttribute');
        self::assertSame($expected, $actual);
    }

    public function testPermissionEmpty(): void
    {
        $permission = new FlagBag(EntityPermission::class);
        $expected = $permission->getValue();
        $this->CalculationRights = $permission;
        $actual = $this->CalculationRights->getValue();
        self::assertSame($expected, $actual);
    }

    public function testPermissionShow(): void
    {
        $expected = EntityPermission::SHOW->value;
        $permission = new FlagBag(EntityPermission::class, $expected);
        $this->CalculationRights = $permission;
        $actual = $this->CalculationRights->getValue();
        self::assertSame($expected, $actual);
    }

    private function checkAttribute(string $field, string $key): void
    {
        $attribute = $this->getAttribute($key);
        $rights = new FlagBag(EntityPermission::class, $attribute);
        $this->$field = $rights;
        /** @psalm-var FlagBag<EntityPermission> $value */
        $value = $this->$field;
        $expected = $rights->getValue();
        $actual = $value->getValue();
        self::assertSame($expected, $actual);
    }

    private function getAttribute(string $key): int
    {
        $permission = EntityPermission::tryFromName($key);

        return $permission instanceof EntityPermission ? $permission->value : EntityPermission::INVALID_VALUE;
    }
}
