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

namespace App\Tests\EntityTrait;

use App\Entity\Category;
use App\Entity\Task;

/**
 * Trait to manage a task.
 */
trait TaskTrait
{
    use CategoryTrait;

    private ?Task $task = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteTask(): void
    {
        if ($this->task instanceof Task) {
            $this->task = $this->deleteEntity($this->task);
        }
        $this->deleteCategory();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function getTask(?Category $category = null, string $name = 'Test Task'): Task
    {
        if ($this->task instanceof Task) {
            return $this->task;
        }

        $this->task = new Task();
        $this->task->setName($name);

        $category ??= $this->getCategory();
        $category->addTask($this->task);

        return $this->addEntity($this->task);
    }
}
