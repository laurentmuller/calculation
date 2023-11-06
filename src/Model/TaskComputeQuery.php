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
readonly class TaskComputeQuery
{
    /** @psalm-var int[] */
    private array $items;

    public function __construct(
        private int $id,
        private float $quantity = 1.0,
        array $items = []
    ) {
        $this->items = \array_map('intval', $items);
    }

    public function getId(): int
    {
        return $this->id;
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

    public static function instance(Task $task, float $quantity = 1.0): self
    {
        $items = $task->getItems()->map(static fn (TaskItem $item): int => (int) $item->getId())->toArray();

        return new self((int) $task->getId(), $quantity, $items);
    }
}
