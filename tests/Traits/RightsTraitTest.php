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
use App\Security\EntityVoter;
use App\Traits\RightsTrait;
use Elao\Enum\FlagBag;
use PHPUnit\Framework\TestCase;

/***
 * Unit test for {@link RightsTrait} class.
 *
 *
 *
 * @see RightsTrait
 */
class RightsTraitTest extends TestCase
{
    use RightsTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->rights = null;
    }

    public function getAttributes(): \Generator
    {
        $values = \array_values(EntityPermission::constants());
        foreach ($values as $value) {
            yield [$value];
        }
    }

    public function getEntities(): \Generator
    {
        $values = \array_values(EntityName::constants());
        foreach ($values as $value) {
            yield [$value];
        }
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetAdd(string $entity): void
    {
        $this->checkAttribute($entity, 'ADD');
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetDelete(string $entity): void
    {
        $this->checkAttribute($entity, 'DELETE');
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetEdit(string $entity): void
    {
        $this->checkAttribute($entity, 'EDIT');
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetEmpty(string $entity): void
    {
        /** @psalm-var FlagBag<EntityPermission> $entity */
        $entity = $this->$entity;
        $this->assertSame(0, $entity->getValue());
    }

    public function testInvalidAttribute(): void
    {
        $attribute = $this->getAttribute('UnknownAttribute');
        $this->assertTrue(EntityVoter::INVALID_VALUE === $attribute);
    }

    public function testIsNotSet(): void
    {
        $className = 'UnknownClass';
        $this->assertFalse($this->__isset($className));
        $value = $this->__get($className);
        $this->assertNull($value);
    }

    /**
     * @dataProvider getEntities
     */
    public function testIsSet(string $entity): void
    {
        $this->assertTrue($this->__isset($entity));
        /** @psalm-var FlagBag<EntityPermission> $entity */
        $entity = $this->$entity;
        $this->assertInstanceOf(FlagBag::class, $entity);
        $this->assertSame(0, $entity->getValue());
    }

    private function checkAttribute(string $entity, string $key): void
    {
        $attribute = $this->getAttribute($key);
        $rights = new FlagBag(EntityPermission::class, $attribute);
        $this->$entity = $rights;
        /** @psalm-var FlagBag<EntityPermission> $value */
        $value = $this->$entity;
        $this->assertEquals($rights, $value);
    }

    private function getAttribute(string $key): int
    {
        $permission = EntityPermission::tryFromName($key);

        return $permission instanceof EntityPermission ? $permission->value : EntityVoter::INVALID_VALUE;
    }
}
