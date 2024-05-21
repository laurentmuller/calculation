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

/**
 * Contains parameters to compute a task.
 */
readonly class TaskComputeQuery
{
    public function __construct(
        public int $id,
        public float $quantity = 1.0,
        /** @var int[] */
        public array $items = []
    ) {
    }

    /**
     * Create a new instance for the given task and quantity.
     */
    public static function instance(Task $task, float $quantity = 1.0): self
    {
        return new self((int) $task->getId(), $quantity, $task->getIdentifiers());
    }
}
