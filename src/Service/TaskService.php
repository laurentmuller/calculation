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
use Symfony\Component\HttpFoundation\Request;

/**
 * Service to compute a task.
 */
class TaskService
{
    use RequestTrait;

    public function __construct(private readonly TaskRepository $repository)
    {
    }

    public function computeQuery(TaskComputeQuery $query): TaskComputeResult
    {
        $task = $query->getTask();
        $quantity = $query->getQuantity();
        $result = new TaskComputeResult($task, $quantity);

        $items = $query->getItems();
        $taskItems = $task->getItems();
        foreach ($taskItems as $taskItem) {
            $id = (int) $taskItem->getId();
            $name = (string) $taskItem->getName();
            if (\in_array($id, $items, true)) {
                $value = $taskItem->findValue($quantity);
                $result->addCheckedResult($id, $name, $value);
            } else {
                $result->addUncheckedResult($id, $name);
            }
        }

        return $result;
    }

    public function createQuery(Request $request): ?TaskComputeQuery
    {
        $id = $this->getRequestInt($request, 'id');
        $task = $this->repository->find($id);
        if (!$task instanceof Task) {
            return null;
        }

        $quantity = $this->getRequestFloat($request, 'quantity');
        $items = \array_map('intval', $this->getRequestAll($request, 'items'));
        $query = new TaskComputeQuery($task);
        $query->setQuantity($quantity)
            ->setItems($items);

        return $query;
    }

    public function getSortedTasks(): array
    {
        return $this->repository->getSortedBuilder(false)
            ->getQuery()
            ->getResult();
    }
}
