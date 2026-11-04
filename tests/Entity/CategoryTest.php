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
use App\Entity\Product;
use App\Entity\Task;

final class CategoryTest extends EntityValidatorTestCase
{
    public function testClone(): void
    {
        $object = new Category();
        $object->withCode('code');

        $clone = $object->clone();
        self::assertSame($object->code, $clone->code);

        $clone = $object->clone('new-code');
        self::assertNotSame($object->code, $clone->code);
    }

    public function testCompare(): void
    {
        $item1 = new Category();
        $item1->withCode('Code1');
        $item2 = new Category();
        $item2->withCode('Code2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testCount(): void
    {
        $object = new Category();
        self::assertSame(0, $object->countProducts());
        self::assertSame(0, $object->countTasks());
        self::assertSame(0, $object->countItems());
        self::assertEmpty($object->getProducts());
        self::assertEmpty($object->getTasks());
        self::assertTrue($object->isEmptyProducts());
        self::assertTrue($object->isEmptyTasks());

        $product = new Product();
        $object->addProduct($product);
        self::assertSame(1, $object->countProducts());
        self::assertSame(0, $object->countTasks());
        self::assertSame(1, $object->countItems());
        self::assertCount(1, $object->getProducts());
        self::assertEmpty($object->getTasks());
        self::assertFalse($object->isEmptyProducts());
        self::assertTrue($object->isEmptyTasks());

        $task = new Task();
        $object->addTask($task);
        self::assertSame(1, $object->countProducts());
        self::assertSame(1, $object->countTasks());
        self::assertSame(2, $object->countItems());
        self::assertCount(1, $object->getProducts());
        self::assertCount(1, $object->getTasks());
        self::assertFalse($object->isEmptyProducts());
        self::assertFalse($object->isEmptyTasks());

        $object->removeProduct($product);
        self::assertSame(0, $object->countProducts());
        self::assertSame(1, $object->countTasks());
        self::assertSame(1, $object->countItems());
        self::assertEmpty($object->getProducts());
        self::assertCount(1, $object->getTasks());
        self::assertTrue($object->isEmptyProducts());
        self::assertFalse($object->isEmptyTasks());

        $object->removeTask($task);
        self::assertSame(0, $object->countProducts());
        self::assertSame(0, $object->countTasks());
        self::assertSame(0, $object->countItems());
        self::assertEmpty($object->getProducts());
        self::assertEmpty($object->getTasks());
        self::assertTrue($object->isEmptyProducts());
        self::assertTrue($object->isEmptyTasks());
    }

    public function testDescription(): void
    {
        $object = new Category();
        $object->withDescription('description');
        self::assertSame('description', $object->description);
    }

    public function testDuplicate(): void
    {
        $group = new Group();
        $group->withCode('group');
        $first = new Category();
        $first->withCode('code')
            ->withGroup($group);

        try {
            $this->saveEntity($group);
            $this->saveEntity($first);
            $second = new Category();
            $second->withCode('code')
                ->withGroup($group);
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'code');
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($group);
        }
    }

    public function testGroup(): void
    {
        $category = new Category();
        self::assertNull($category->group);
        self::assertNull($category->getGroupId());
        self::assertNull($category->getGroupCode());

        $group = new Group();
        $group->withCode('group');
        $category->withGroup($group);
        self::assertNotNull($category->group);
        self::assertSame('group', $category->getGroupCode());
    }

    public function testInvalidCode(): void
    {
        $group = new Group();
        $group->withCode('group');
        $object = new Category();
        $object->withGroup($group);
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'code');
    }

    public function testNotDuplicate(): void
    {
        $group = new Group();
        $group->withCode('group');
        $first = new Category();
        $first->withCode('code')
            ->withGroup($group);

        try {
            $this->saveEntity($group);
            $this->saveEntity($first);

            $second = new Category();
            $second->withCode('code2')
                ->withGroup($group);

            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $group = new Group();
        $group->withCode('group');
        $object = new Category();
        $object->withCode('code');
        $object->withDescription('description');
        $object->withGroup($group);
        $this->validate($object);
    }
}
