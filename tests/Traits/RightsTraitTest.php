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

use App\Interfaces\EntityVoterInterface;
use App\Security\EntityVoter;
use App\Traits\RightsTrait;
use PHPUnit\Framework\TestCase;

/***
 * Unit test for {@link App\Traits\RightsTrait} class.
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

    public function getEntities(): array
    {
        return [\array_keys(EntityVoter::ENTITY_OFFSETS)];
    }

    public function getMaskAttributes(): array
    {
        return [EntityVoter::MASK_ATTRIBUTES];
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetAdd(string $entity): void
    {
        $this->checkAttribute($entity, EntityVoterInterface::ATTRIBUTE_ADD);
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetDelete(string $entity): void
    {
        $this->checkAttribute($entity, EntityVoterInterface::ATTRIBUTE_DELETE);
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetEdit(string $entity): void
    {
        $this->checkAttribute($entity, EntityVoterInterface::ATTRIBUTE_EDIT);
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetEmpty(string $entity): void
    {
        $this->assertEmpty($this->$entity);
    }

    public function testInvalidAttribute(): void
    {
        $attribute = $this->getAttribute('UnknowAttribute');
        $this->assertTrue(EntityVoter::INVALID_VALUE === $attribute);
    }

    public function testIsNotSet(): void
    {
        $className = 'UnknowClass';
        $this->assertFalse($this->__isset($className));
        // @phpstan-ignore-next-line
        $this->assertNull($this->$className);
    }

    /**
     * @dataProvider getEntities
     */
    public function testIsSet(string $entity): void
    {
        $this->assertTrue($this->__isset($entity));
        $this->assertIsArray($this->$entity);
        $this->assertTrue(0 === \count($this->$entity));
    }

    private function checkAttribute(string $entity, string $key): void
    {
        $attribute = $this->getAttribute($key);
        $rights = [$key => $attribute];
        $this->$entity = $rights;
        $value = $this->$entity;
        $this->assertSame($rights, $value);
    }

    private function getAttribute(string $key): int
    {
        if (\array_key_exists($key, EntityVoter::MASK_ATTRIBUTES)) {
            return EntityVoter::MASK_ATTRIBUTES[$key];
        }

        return EntityVoter::INVALID_VALUE;
    }
}
