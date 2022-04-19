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

/**
 * Unit test for {@link App\Entity\TaskItem} class.
 *
 * @author Laurent Muller
 */
class TaskItemTest extends AbstractEntityValidatorTest
{
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
            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($task);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testFindMargin(): void
    {
        $item = new TaskItem();
        $item->addMargin($this->createMargin(0, 100, 10));
        $this->assertNull($item->findMargin(-1));
        $this->assertNull($item->findMargin(101));
        $this->assertNotNull($item->findMargin(0));
    }

    public function testFindValue(): void
    {
        $item = new TaskItem();
        $item->addMargin($this->createMargin(0, 100, 0.1));
        $this->assertEqualsWithDelta(0.1, $item->findValue(50), 0.01);
        $this->assertEqualsWithDelta(0, $item->findValue(100), 0.01);
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
            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($task);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testValid(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category);
        $item = $this->createTaskItem($task);
        $item->setName('name');
        $this->validate($task, 0);
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

    private function createMargin(float $minimum, float $maximum, float $value): TaskItemMargin
    {
        $margin = new TaskItemMargin();
        $margin->setMinimum($minimum)
            ->setMaximum($maximum)
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
