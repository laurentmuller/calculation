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

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteTaskItem(): void
    {
        if ($this->taskItem instanceof TaskItem) {
            $this->taskItem = $this->deleteEntity($this->taskItem);
        }
        $this->deleteTask();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function getTaskItem(Task $task, string $name = 'Test Task Item'): TaskItem
    {
        if (!$this->taskItem instanceof TaskItem) {
            $this->taskItem = new TaskItem();
            $this->taskItem->setTask($task)
                ->setName($name);
            $this->addEntity($this->taskItem);
        }

        return $this->taskItem; // @phpstan-ignore-line
    }
}
