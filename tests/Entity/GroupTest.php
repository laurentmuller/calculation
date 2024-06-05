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

use App\Entity\AbstractCodeEntity;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Entity\Product;
use App\Entity\Task;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Group::class)]
#[CoversClass(AbstractCodeEntity::class)]
class GroupTest extends AbstractEntityValidatorTestCase
{
    use IdTrait;

    public function testCategory(): void
    {
        $group = new Group();
        self::assertFalse($group->hasCategories());
        self::assertCount(0, $group->getCategories());
        self::assertSame(0, $group->countCategories());

        $category = new Category();
        $group->addCategory($category);
        self::assertTrue($group->hasCategories());
        self::assertCount(1, $group->getCategories());
        self::assertSame(1, $group->countCategories());

        $group->removeCategory($category);
        self::assertFalse($group->hasCategories());
        self::assertCount(0, $group->getCategories());
        self::assertSame(0, $group->countCategories());
    }

    /**
     * @throws \ReflectionException
     */
    public function testClone(): void
    {
        $group = new Group();
        $margin = $this->createMargin();
        self::setId($margin, 10);
        $group->addMargin($margin);

        $clone = clone $group;
        foreach ($clone->getMargins() as $currentMargin) {
            self::assertNull($currentMargin->getId());
        }

        $clone = $group->clone();
        self::assertNull($clone->getCode());

        $clone = $group->clone('new-code');
        self::assertSame('new-code', $clone->getCode());
    }

    public function testCompare(): void
    {
        $item1 = new Group();
        $item1->setCode('Code1');
        $item2 = new Group();
        $item2->setCode('Code2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testCount(): void
    {
        $group = new Group();
        self::assertSame(0, $group->countCategories());
        self::assertSame(0, $group->countItems());
        self::assertSame(0, $group->countMargins());
        self::assertSame(0, $group->countProducts());
        self::assertSame(0, $group->countTasks());

        self::assertFalse($group->hasCategories());
        self::assertFalse($group->hasMargins());
        self::assertFalse($group->hasProducts());
        self::assertFalse($group->hasTasks());

        $group->addMargin(new GroupMargin());
        self::assertSame(0, $group->countCategories());
        self::assertSame(0, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(0, $group->countProducts());
        self::assertSame(0, $group->countTasks());

        self::assertFalse($group->hasCategories());
        self::assertTrue($group->hasMargins());
        self::assertFalse($group->hasProducts());
        self::assertFalse($group->hasTasks());

        $category = new Category();
        $group->addCategory($category);
        self::assertSame(0, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(0, $group->countProducts());
        self::assertSame(0, $group->countTasks());

        self::assertTrue($group->hasCategories());
        self::assertTrue($group->hasMargins());
        self::assertFalse($group->hasProducts());
        self::assertFalse($group->hasTasks());

        $category->addProduct(new Product());
        self::assertSame(1, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(1, $group->countProducts());
        self::assertSame(0, $group->countTasks());

        self::assertTrue($group->hasCategories());
        self::assertTrue($group->hasMargins());
        self::assertTrue($group->hasProducts());
        self::assertFalse($group->hasTasks());

        $category->addTask(new Task());
        self::assertSame(2, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(1, $group->countProducts());
        self::assertSame(1, $group->countTasks());

        self::assertTrue($group->hasCategories());
        self::assertTrue($group->hasMargins());
        self::assertTrue($group->hasProducts());
        self::assertTrue($group->hasTasks());
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testDuplicate(): void
    {
        $first = new Group();
        $first->setCode('code');

        try {
            $this->saveEntity($first);
            $second = new Group();
            $second->setCode('code');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'code');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFields(): void
    {
        $group = new Group();
        self::assertNull($group->getDescription());
        $group->setDescription('description');
        self::assertSame('description', $group->getDescription());
    }

    public function testFindMargin(): void
    {
        $group = new Group();
        self::assertNull($group->findMargin(0));
        $group->addMargin($this->createMargin());
        self::assertNotNull($group->findMargin(0));
        self::assertNull($group->findMargin(100));
    }

    public function testFindPercent(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin());
        self::assertEqualsWithDelta(1.1, $group->findPercent(50), 0.01);
        self::assertEqualsWithDelta(0, $group->findPercent(100), 0.01);
    }

    public function testGroupMargin(): void
    {
        $group = new Group();
        self::assertFalse($group->hasMargins());
        self::assertCount(0, $group->getMargins());
        self::assertSame(0, $group->countMargins());

        $margin = $this->createMargin();
        $group->addMargin($margin);
        self::assertTrue($group->hasMargins());
        self::assertCount(1, $group->getMargins());
        self::assertSame(1, $group->countMargins());

        $group->removeMargin($margin);
        self::assertFalse($group->hasMargins());
        self::assertCount(0, $group->getMargins());
        self::assertSame(0, $group->countMargins());
    }

    public function testInvalidCode(): void
    {
        $object = new Group();
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'code');
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testNotDuplicate(): void
    {
        $first = new Group();
        $first->setCode('code1');

        try {
            $this->saveEntity($first);
            $second = new Group();
            $second->setCode('code2');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new Group();
        $object->setCode('code');
        $this->validate($object);
    }

    private function createMargin(): GroupMargin
    {
        $margin = new GroupMargin();
        $margin->setMinimum(0)
            ->setMaximum(100)
            ->setMargin(1.1);

        return $margin;
    }
}
