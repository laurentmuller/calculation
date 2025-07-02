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
use App\Entity\TaskItemMargin;

class TaskItemTest extends EntityValidatorTestCase
{
    use IdTrait;

    /**
     * @throws \ReflectionException
     */
    public function testClone(): void
    {
        $item = new TaskItem();
        $margin = $this->createMargin(0);
        self::setId($margin);
        $item->addMargin($margin);

        $clone = clone $item;
        foreach ($clone->getMargins() as $currentMargin) {
            self::assertNull($currentMargin->getId());
        }
    }

    public function testCompare(): void
    {
        $item1 = new TaskItem();
        $item1->setName('TaskItem1');
        $item2 = new TaskItem();
        $item2->setName('TaskItem2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testDuplicate(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category);
        $item = $this->createTaskItem($task);
        $item->setName('name');
        $task->addItem($item);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($task);
            $second = $this->createTaskItem($task)->setName('name');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'name');
        } finally {
            $this->deleteEntity($task);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testFindMargin(): void
    {
        $item = new TaskItem();
        $item->addMargin($this->createMargin(10));
        self::assertNull($item->findMargin(-1));
        self::assertNull($item->findMargin(101));
        self::assertNotNull($item->findMargin(0));
    }

    public function testFindValue(): void
    {
        $item = new TaskItem();
        $item->addMargin($this->createMargin(0.1));
        self::assertEqualsWithDelta(0.1, $item->findValue(50), 0.01);
        self::assertEqualsWithDelta(0, $item->findValue(100), 0.01);
    }

    public function testNameAndDisplay(): void
    {
        $item = new TaskItem();
        self::assertNull($item->getName());
        self::assertSame('', $item->getDisplay());

        $item->setName('name');
        self::assertSame('name', $item->getName());
        self::assertSame('name', $item->getDisplay());
    }

    public function testNotDuplicate(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category);
        $item = $this->createTaskItem($task);
        $item->setName('name1');
        $task->addItem($item);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($task);
            $second = $this->createTaskItem($task)->setName('name2');
            $this->validate($second);
        } finally {
            $this->deleteEntity($task);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testTaskMargin(): void
    {
        $item = new TaskItem();
        self::assertCount(0, $item);
        self::assertEmpty($item->getMargins());
        self::assertTrue($item->isEmpty());

        $margin = $this->createMargin(0);
        $item->addMargin($margin);
        self::assertCount(1, $item);
        self::assertCount(1, $item->getMargins());
        self::assertFalse($item->isEmpty());

        // not add duplicate
        $item->addMargin($margin);
        self::assertCount(1, $item);

        $item->removeMargin($margin);
        self::assertCount(0, $item);
        self::assertEmpty($item->getMargins());
        self::assertTrue($item->isEmpty());
    }

    public function testValid(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category);
        $item = $this->createTaskItem($task);
        $item->setName('name');
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

    private function createMargin(float $value): TaskItemMargin
    {
        $margin = new TaskItemMargin();
        $margin->setMinimum(0)
            ->setMaximum(100)
            ->setValue($value);

        return $margin;
    }

    private function createTask(Category $category): Task
    {
        $task = new Task();
        $task->setName('name')->setCategory($category);

        return $task;
    }

    private function createTaskItem(Task $task): TaskItem
    {
        $item = new TaskItem();
        $task->addItem($item);

        return $item;
    }
}
