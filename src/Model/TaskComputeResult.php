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
 * Contains result of a computed task.
 */
class TaskComputeResult implements \JsonSerializable
{
    private float $overall = 0;

    /**
     * @var array<array{id: int, name: string, value: float, amount: float, checked: bool}>
     */
    private array $results = [];

    public function __construct(private readonly Task $task, private readonly float $quantity)
    {
    }

    public function addCheckedResult(int $id, string $name, float $value): self
    {
        return $this->addResult($id, $name, $value, true);
    }

    public function addUncheckedResult(int $id, string $name): self
    {
        return $this->addResult($id, $name, 0, false);
    }

    public function getOverall(): float
    {
        return $this->overall;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @return array<array{id: int, name: string, value: float, amount: float, checked: bool}>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * {@inheritdoc}
     */
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

    private function addResult(int $id, string $name, float $value, bool $checked): self
    {
        $amount = $this->quantity * $value;
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
}
