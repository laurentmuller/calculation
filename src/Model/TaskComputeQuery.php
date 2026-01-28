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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contains parameters to compute a task.
 */
class TaskComputeQuery
{
    public function __construct(
        #[Assert\Positive]
        public int $id = 0,
        #[Assert\PositiveOrZero]
        public float $quantity = 1.0,
        /** @var int[] */
        #[Assert\All([
            new Assert\Type('int'),
            new Assert\Positive(),
        ])]
        #[Assert\Unique]
        #[Assert\Count(min: 1)]
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
