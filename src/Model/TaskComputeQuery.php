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

namespace App\Model;

use App\Entity\Task;
use App\Entity\TaskItem;

/**
 * Contains parameters to compute a task.
 */
class TaskComputeQuery
{
    /**
     * @var int[]
     */
    private array $items = [];

    private float $quantity = 1;

    public function __construct(private readonly Task $task, bool $selectAll = false)
    {
        if ($selectAll) {
            $this->updateItems();
        }
    }

    /**
     * @return int[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getUnit(): ?string
    {
        return $this->task->getUnit();
    }

    /**
     * @param int[] $items
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    private function updateItems(): void
    {
        $this->items = $this->task->getItems()->map(fn (TaskItem $item) => (int) $item->getId())->toArray();
    }
}
