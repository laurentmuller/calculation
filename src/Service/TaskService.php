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

namespace App\Service;

use App\Entity\Task;
use App\Model\TaskComputeQuery;
use App\Model\TaskComputeResult;
use App\Repository\TaskRepository;
use App\Traits\RequestTrait;

/**
 * Service to compute a task.
 */
class TaskService
{
    use RequestTrait;

    public function __construct(private readonly TaskRepository $repository)
    {
    }

    /**
     * Compute result for the given query.
     */
    public function computeQuery(TaskComputeQuery $query): ?TaskComputeResult
    {
        $task = $this->repository->find($query->getId());
        if (!$task instanceof Task) {
            return null;
        }

        $quantity = $query->getQuantity();
        $selectedItems = $query->getItems();
        $result = new TaskComputeResult($task, $quantity);
        foreach ($task->getItems() as $item) {
            $result->addItem($item, \in_array($item->getId(), $selectedItems, true));
        }

        return $result;
    }

    /**
     * @return Task[]
     */
    public function getSortedTasks(): array
    {
        return $this->repository->getSortedTask(false);
    }
}
