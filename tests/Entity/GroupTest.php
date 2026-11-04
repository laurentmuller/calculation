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

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Entity\Product;
use App\Entity\Task;

final class GroupTest extends EntityValidatorTestCase
{
    use IdTrait;

    public function testCategory(): void
    {
        $group = new Group();
        self::assertTrue($group->isEmptyCategories());
        self::assertEmpty($group->categories);
        self::assertSame(0, $group->countCategories());

        $category = new Category();
        $group->addCategory($category);
        self::assertFalse($group->isEmptyCategories());
        self::assertCount(1, $group->categories);
        self::assertSame(1, $group->countCategories());

        $group->removeCategory($category);
        self::assertTrue($group->isEmptyCategories());
        self::assertEmpty($group->categories);
        self::assertSame(0, $group->countCategories());
    }

    public function testClone(): void
    {
        $group = new Group();
        $margin = $this->createMargin();
        self::setId($margin, 10);
        $group->addMargin($margin);

        $clone = clone $group;
        foreach ($clone->margins as $currentMargin) {
            self::assertNull($currentMargin->id);
        }

        $clone = $group->clone();
        self::assertNull($clone->code);

        $clone = $group->clone('new-code');
        self::assertSame('new-code', $clone->code);
    }

    public function testCompare(): void
    {
        $item1 = new Group();
        $item1->withCode('Code1');
        $item2 = new Group();
        $item2->withCode('Code2');
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

        self::assertTrue($group->isEmptyCategories());
        self::assertTrue($group->isEmptyMargins());
        self::assertTrue($group->isEmptyProducts());
        self::assertTrue($group->isEmptyTasks());

        $group->addMargin(new GroupMargin());
        self::assertSame(0, $group->countCategories());
        self::assertSame(0, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(0, $group->countProducts());
        self::assertSame(0, $group->countTasks());

        self::assertTrue($group->isEmptyCategories());
        self::assertFalse($group->isEmptyMargins());
        self::assertTrue($group->isEmptyProducts());
        self::assertTrue($group->isEmptyTasks());

        $category = new Category();
        $group->addCategory($category);
        self::assertSame(0, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(0, $group->countProducts());
        self::assertSame(0, $group->countTasks());

        self::assertFalse($group->isEmptyCategories());
        self::assertFalse($group->isEmptyMargins());
        self::assertTrue($group->isEmptyProducts());
        self::assertTrue($group->isEmptyTasks());

        $category->addProduct(new Product());
        self::assertSame(1, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(1, $group->countProducts());
        self::assertSame(0, $group->countTasks());

        self::assertFalse($group->isEmptyCategories());
        self::assertFalse($group->isEmptyMargins());
        self::assertFalse($group->isEmptyProducts());
        self::assertTrue($group->isEmptyTasks());

        $category->addTask(new Task());
        self::assertSame(2, $group->countItems());
        self::assertSame(1, $group->countMargins());
        self::assertSame(1, $group->countProducts());
        self::assertSame(1, $group->countTasks());

        self::assertFalse($group->isEmptyCategories());
        self::assertFalse($group->isEmptyMargins());
        self::assertFalse($group->isEmptyProducts());
        self::assertFalse($group->isEmptyTasks());
    }

    public function testDuplicate(): void
    {
        $first = new Group();
        $first->withCode('code');

        try {
            $this->saveEntity($first);
            $second = new Group();
            $second->withCode('code');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'code');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFields(): void
    {
        $group = new Group();
        self::assertNull($group->description);
        $group->withDescription('description');
        self::assertSame('description', $group->description);
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
        self::assertTrue($group->isEmptyMargins());
        self::assertEmpty($group->margins);
        self::assertSame(0, $group->countMargins());

        $margin = $this->createMargin();
        $group->addMargin($margin);
        self::assertFalse($group->isEmptyMargins());
        self::assertCount(1, $group->margins);
        self::assertSame(1, $group->countMargins());

        $group->removeMargin($margin);
        self::assertTrue($group->isEmptyMargins());
        self::assertEmpty($group->margins);
        self::assertSame(0, $group->countMargins());
    }

    public function testInvalidCode(): void
    {
        $object = new Group();
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'code');
    }

    public function testNotDuplicate(): void
    {
        $first = new Group();
        $first->withCode('code1');

        try {
            $this->saveEntity($first);
            $second = new Group();
            $second->withCode('code2');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new Group();
        $object->withCode('code');
        $this->validate($object);
    }

    private function createMargin(): GroupMargin
    {
        return new GroupMargin()
            ->withMinimum(0)
            ->withMaximum(100)
            ->withMargin(1.1);
    }
}
