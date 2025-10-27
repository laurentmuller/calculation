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
use App\Entity\Task;
use App\Entity\TaskItem;

final class TaskTest extends EntityValidatorTestCase
{
    use IdTrait;

    public function testClone(): void
    {
        $task = new Task();
        $task->setName('name');

        $clone = $task->clone();
        self::assertSame($task->getName(), $clone->getName());

        $clone = $task->clone('clone');
        self::assertNotSame($task->getName(), $clone->getName());
        self::assertSame('clone', $clone->getName());
    }

    public function testCompare(): void
    {
        $item1 = new Task();
        $item1->setName('Task1');
        $item2 = new Task();
        $item2->setName('Task2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testDuplicate(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $first = $this->createTask($category, 'name');

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);
            $second = $this->createTask($category, 'name');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'name');
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetIdentifiers(): void
    {
        $task = new Task();
        $actual = $task->getIdentifiers();
        self::assertSame([], $actual);

        $item = new TaskItem();
        self::setId($item);
        $task->addItem($item);
        $item = new TaskItem();
        self::setId($item, 2);
        $task->addItem($item);
        $actual = $task->getIdentifiers();
        self::assertSame([1, 2], $actual);
    }

    public function testInvalidCategory(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category, 'name');
        $task->setCategory(null);
        $results = $this->validate($task, 1);
        $this->validatePaths($results, 'category');
    }

    public function testInvalidName(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category);
        $results = $this->validate($task, 1);
        $this->validatePaths($results, 'name');
    }

    public function testNotDuplicate(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $first = $this->createTask($category, 'name1');

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);
            $second = $this->createTask($category, 'name2');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testTaskItem(): void
    {
        $task = new Task();
        self::assertTrue($task->isEmpty());
        self::assertCount(0, $task);
        self::assertSame(0, $task->countMargins());
        self::assertEmpty($task->getItems());

        $item = new TaskItem();
        $task->removeItem($item);
        self::assertCount(0, $task);

        $task->addItem($item);
        self::assertFalse($task->isEmpty());
        self::assertCount(1, $task);
        self::assertSame(0, $task->countMargins());
        self::assertCount(1, $task->getItems());

        // not add duplicate
        $task->addItem($item);
        self::assertCount(1, $task);

        $task->removeItem($item);
        self::assertTrue($task->isEmpty());
        self::assertCount(0, $task);
        self::assertSame(0, $task->countMargins());
        self::assertEmpty($task->getItems());
    }

    public function testUpdatePosition(): void
    {
        $task = new Task();

        $item0 = new TaskItem();
        $item0->setPosition(-1);

        $item1 = new TaskItem();
        $item1->setPosition(-2);

        $task->addItem($item0);
        $task->addItem($item1);
        $task->updatePositions();

        self::assertSame(0, $item0->getPosition());
        self::assertSame(1, $item1->getPosition());
    }

    public function testValid(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category, 'name');
        $this->validate($task);
    }

    private function createCategory(Group $group): Category
    {
        $category = new Category();
        $category->setCode('code');
        $category->setGroup($group);

        return $category;
    }

    private function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('code');

        return $group;
    }

    private function createTask(Category $category, ?string $name = null): Task
    {
        $task = new Task();
        $task->setCategory($category);
        if (null !== $name) {
            $task->setName($name);
        }

        return $task;
    }
}
