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

use App\Entity\Task;
use App\Entity\TaskItem;

/**
 * Trait to manage a task item.
 */
trait TaskItemTrait
{
    use TaskTrait;

    private ?TaskItem $taskItem = null;

    protected function deleteTaskItem(): void
    {
        if ($this->taskItem instanceof TaskItem) {
            $this->taskItem = $this->deleteEntity($this->taskItem);
        }
        $this->deleteTask();
    }

    protected function getTaskItem(?Task $task = null, string $name = 'Test Task Item'): TaskItem
    {
        if ($this->taskItem instanceof TaskItem) {
            return $this->taskItem;
        }

        $task ??= $this->getTask();
        $this->taskItem = new TaskItem();
        $this->taskItem->setName($name);
        $task->addItem($this->taskItem);

        return $this->addEntity($this->taskItem);
    }
}
