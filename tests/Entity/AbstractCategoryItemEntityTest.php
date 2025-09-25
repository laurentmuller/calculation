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

namespace App\Tests\Entity;

use App\Entity\AbstractCategoryItemEntity;
use App\Entity\Category;
use App\Entity\Group;
use PHPUnit\Framework\TestCase;

class AbstractCategoryItemEntityTest extends TestCase
{
    use IdTrait;

    /**
     * @throws \ReflectionException
     */
    public function testCategoryAndGroup(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getCategory());
        self::assertNull($entity->getCategoryId());
        self::assertSame('', $entity->getCategoryCode());
        self::assertNull($entity->getGroup());
        self::assertSame('', $entity->getGroupCode());

        $category = new Category();
        $category->setCode('code');
        self::setId($category);
        $entity->setCategory($category);
        self::assertSame($category, $entity->getCategory());
        self::assertSame(1, $entity->getCategoryId());
        self::assertSame('code', $entity->getCategoryCode());
        self::assertNull($entity->getGroup());

        $group = new Group();
        $group->setCode('group');
        $group->addCategory($category);

        self::assertNotNull($entity->getGroup());
        self::assertSame('group', $entity->getGroupCode());
    }

    /**
     * @throws \ReflectionException
     */
    public function testFields(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getUnit());
        self::assertNull($entity->getSupplier());

        $entity->setUnit('unit');
        $entity->setSupplier('supplier');
        self::assertSame('unit', $entity->getUnit());
        self::assertSame('supplier', $entity->getSupplier());
    }

    /**
     * @throws \ReflectionException
     */
    private function getEntity(?int $id = null): AbstractCategoryItemEntity
    {
        $entity = new class extends AbstractCategoryItemEntity {};
        if (\is_int($id)) {
            return self::setId($entity, $id);
        }

        return $entity;
    }
}
