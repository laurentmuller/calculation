<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Task;

/**
 * Unit test for {@link App\Entity\Task} class.
 *
 * @author Laurent Muller
 */
class TaskTest extends AbstractEntityValidatorTest
{
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
            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testInvalidCategory(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category, 'name');
        $task->setCategory(null);
        $this->validate($task, 1);
    }

    public function testInvalidName(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category);
        $this->validate($task, 1);
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
            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testValid(): void
    {
        $group = $this->createGroup();
        $category = $this->createCategory($group);
        $task = $this->createTask($category, 'name');
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
