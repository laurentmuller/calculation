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
 * Contains the result of a computed task.
 *
 * @psalm-type ItemType = array{
 *     id: int,
 *     name: string,
 *     value: float,
 *     amount: float,
 *     checked: bool}
 */
class TaskComputeResult implements \JsonSerializable
{
    /**
     * @psalm-var ItemType[]
     */
    private array $items = [];
    private float $overall = 0;

    public function __construct(private readonly Task $task, private readonly float $quantity)
    {
    }

    public function addItem(TaskItem $item, bool $checked): self
    {
        $id = (int) $item->getId();
        $name = (string) $item->getName();
        $value = $item->findValue($this->quantity);
        $amount = $checked ? $this->quantity * $value : 0.0;
        $this->items[] = [
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
     * @psalm-return ItemType[]
     *
     * @psalm-api
     */
    public function getItems(): array
    {
        return $this->items;
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
            'items' => $this->items,
        ];
    }
}
