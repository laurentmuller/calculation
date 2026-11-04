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

final class AbstractCategoryItemEntityTest extends TestCase
{
    use IdTrait;

    public function testCategoryAndGroup(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->category);
        self::assertNull($entity->getCategoryId());
        self::assertSame('', $entity->getCategoryCode());
        self::assertNull($entity->getGroup());
        self::assertSame('', $entity->getGroupCode());

        $category = new Category();
        $category->withCode('code');
        self::setId($category);
        $entity->withCategory($category);
        self::assertSame($category, $entity->category);
        self::assertSame(1, $entity->getCategoryId());
        self::assertSame('code', $entity->getCategoryCode());
        self::assertNull($entity->getGroup());

        $group = new Group();
        $group->withCode('group');
        $group->addCategory($category);

        self::assertNotNull($entity->getGroup());
        self::assertSame('group', $entity->getGroupCode());
    }

    public function testFields(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->unit);
        self::assertNull($entity->supplier);

        $entity->withUnit('unit');
        $entity->withSupplier('supplier');
        self::assertSame('unit', $entity->unit);
        self::assertSame('supplier', $entity->supplier);
    }

    private function getEntity(?int $id = null): AbstractCategoryItemEntity
    {
        $entity = new class extends AbstractCategoryItemEntity {};
        if (\is_int($id)) {
            return self::setId($entity, $id);
        }

        return $entity;
    }
}
