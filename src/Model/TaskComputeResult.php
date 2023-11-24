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
 * Contains result of a computed task.
 *
 * @psalm-type ResultType = array{
 *     id: int,
 *     name: string,
 *     value: float,
 *     amount: float,
 *     checked: bool}
 */
class TaskComputeResult implements \JsonSerializable
{
    private float $overall = 0;

    /**
     * @psalm-var ResultType[]
     */
    private array $results = [];

    public function __construct(private readonly Task $task, private readonly float $quantity)
    {
    }

    public function addItem(TaskItem $item, bool $checked): self
    {
        $id = (int) $item->getId();
        $name = (string) $item->getName();
        $value = $item->findValue($this->quantity);
        $amount = $checked ? $this->quantity * $value : 0.0;
        $this->results[] = [
            'id' => $id,
            'name' => $name,
            'value' => $value,
            'amount' => $amount,
            'checked' => $checked,
        ];
        $this->overall += $amount;

        return $this;
    }

    /**
     * @psalm-api
     */
    public function getOverall(): float
    {
        return $this->overall;
    }

    /**
     * @psalm-api
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @psalm-return ResultType[]
     *
     * @psalm-api
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @psalm-api
     */
    public function getTask(): Task
    {
        return $this->task;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->task->getId(),
            'unit' => $this->task->getUnit(),
            'categoryId' => $this->task->getCategoryId(),
            'quantity' => $this->quantity,
            'overall' => $this->overall,
            'results' => $this->results,
        ];
    }
}
