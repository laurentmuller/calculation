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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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

    #[DataProvider('getRightsFields')]
    public function testGetAdd(string $field): void
    {
        $this->checkAttribute($field, 'ADD');
    }

    #[DataProvider('getRightsFields')]
    public function testGetDelete(string $field): void
    {
        $this->checkAttribute($field, 'DELETE');
    }

    #[DataProvider('getRightsFields')]
    public function testGetEdit(string $field): void
    {
        $this->checkAttribute($field, 'EDIT');
    }

    #[DataProvider('getRightsFields')]
    public function testGetEmpty(string $field): void
    {
        $permission = $this->__get($field);
        self::assertNotNull($permission);
        $expected = 0;
        $actual = $permission->getValue();
        self::assertSame($expected, $actual);
    }

    public function testIsSet(): void
    {
        self::assertFalse($this->__isset('fake'));
        $this->LogRights = FlagBag::fromAll(EntityPermission::class);
        self::assertTrue($this->__isset('LogRights'));
    }

    public function testOverwrite(): void
    {
        self::assertFalse($this->isOverwrite());
        $this->setOverwrite(true);
        self::assertTrue($this->isOverwrite());
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

    /**
     * @psalm-suppress InvalidPropertyAssignmentValue
     * @psalm-suppress UndefinedThisPropertyAssignment
     */
    public function testSetInvalid(): void
    {
        self::assertSame(0, $this->getPermission(EntityName::LOG)->getValue());
        $this->__set('LogRights', null);
        self::assertSame(0, $this->getPermission(EntityName::LOG)->getValue());
        $this->__set('fake', FlagBag::fromAll(EntityPermission::class));
        self::assertSame(0, $this->getPermission(EntityName::LOG)->getValue());
    }

    private function checkAttribute(string $field, string $key): void
    {
        $attribute = $this->getAttribute($key);
        $rights = new FlagBag(EntityPermission::class, $attribute);
        $this->__set($field, $rights);
        /** @psalm-var FlagBag<EntityPermission> $value */
        $value = $this->__get($field);
        $expected = $rights->getValue();
        $actual = $value->getValue();
        self::assertSame($expected, $actual);
    }

    private function getAttribute(string $key): int
    {
        return EntityPermission::tryFromName($key)->value ?? -1;
    }
}
