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

    /**
     * Compute result for the given query.
     */
    public function computeQuery(TaskComputeQuery $query): TaskComputeResult
    {
        $task = $query->getTask();
        $quantity = $query->getQuantity();
        $result = new TaskComputeResult($task, $quantity);
        $keys = $query->getItems();
        $items = $task->getItems();
        foreach ($items as $item) {
            $result->addItem($item, \in_array($item->getId(), $keys, true));
        }

        return $result;
    }

    /**
     * Create a query for the given request.
     */
    public function createQuery(Request $request): ?TaskComputeQuery
    {
        $payload = $request->getPayload();
        $id = $payload->getInt('id');
        $task = $this->repository->find($id);
        if (!$task instanceof Task) {
            return null;
        }

        $quantity = (float) $payload->getString('quantity', '1.0');
        $items = \array_map('intval', $payload->all('items'));
        $query = new TaskComputeQuery($task);
        $query->setQuantity($quantity)
            ->setItems($items);

        return $query;
    }

    /**
     * @return Task[]
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getSortedTasks(): array
    {
        return $this->repository->getSortedBuilder(false)
            ->getQuery()
            ->getResult();
    }
}
