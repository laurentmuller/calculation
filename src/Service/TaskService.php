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

use App\Entity\Category;
use App\Entity\Task;
use App\Entity\TaskItem;
use App\Util\Utils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service to compute task.
 *
 * @author Laurent Muller
 */
class TaskService implements \JsonSerializable
{
    private ?Category $category = null;

    /**
     * @var int[]
     */
    private array $items = [];

    private float $overall = 0.0;

    private float $quantity = 1.0;

    private array $results = [];

    private ?Task $task = null;

    /**
     * Compute values.
     */
    public function compute(Request $request = null): void
    {
        $this->results = [];
        $this->overall = 0.0;
        if (null !== $request) {
            $this->items = $this->parseRequest($request);
        }

        if (null !== $this->task) {
            $quantity = $this->quantity;
            $items = $this->task->getItems();
            foreach ($items as $item) {
                $checked = false;
                $id = $item->getId();
                $value = $amount = 0.0;
                if (\in_array($id, $this->items, true)) {
                    $checked = true;
                    $value = $item->findValue($quantity);
                    $amount = $value * $this->quantity;
                    $this->overall += $amount;
                }
                $this->results[] = [
                    'id' => $id,
                    'name' => $item->getName(),
                    'value' => $value,
                    'amount' => $amount,
                    'checked' => $checked,
                ];
            }
        }
    }

    /**
     * Gets the category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Gets the selected task item identifiers.
     *
     * @return int[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Gets the overall.
     */
    public function getOverall(): float
    {
        return $this->overall;
    }

    /**
     * Gets the quantity.
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * Gets the computed results.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Gets the task.
     */
    public function getTask(): ?Task
    {
        return $this->task;
    }

    /**
     * Gets the selected task items.
     *
     * @return Collection<int, TaskItem>
     */
    public function getTaskItems(): Collection
    {
        if (null !== $this->task && !empty($this->items)) {
            $items = $this->items;

            return $this->task->filter(fn (TaskItem $item) => \in_array($item->getId(), $items, true));
        }

        return new ArrayCollection();
    }

    /**
     * Returns if this service contains valid data.
     */
    public function isValid(): bool
    {
        if (!$this->task instanceof Task || $this->quantity <= 0 || empty($this->items)) {
            return false;
        }

        return !$this->getTaskItems()->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $id = $this->task?->getId();
        $unit = $this->task?->getUnit();
        $categoryId = $this->task?->getCategoryId();

        return [
            'id' => $id,
            'unit' => $unit,
            'categoryId' => $categoryId,
            'quantity' => $this->quantity,
            'overall' => $this->overall,
            'results' => $this->results,
        ];
    }

    /**
     * Sets the category.
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Sets the selected task item identifiers.
     *
     * @param int[] $items
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Sets the quantity.
     */
    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Sets the task.
     *
     * @param Task|null $task      the task to set
     * @param bool      $selectAll true to select all task items for the given task
     */
    public function setTask(?Task $task, bool $selectAll = false): self
    {
        $this->task = $task;

        // select all?
        if (null !== $this->task && $selectAll) {
            return $this->setTaskItems($this->task->getItems());
        }

        return $this;
    }

    /**
     * Sets the selected task item.
     *
     * @param Collection<int, TaskItem> $items
     */
    public function setTaskItems(Collection $items): self
    {
        $task = $this->task;

        /** @psalm-var int[] $items */
        $items = $items->filter(fn (TaskItem $item) => $task === $item->getTask())->map(fn (TaskItem $item) => (int) $item->getId())->toArray();
        $this->items = $items;

        return $this;
    }

    /**
     * @return int[]
     */
    private function parseRequest(Request $request): array
    {
        /** @var int[] $items */
        $items = Utils::getRequestInputBag($request)->all('items');

        return \array_map('intval', $items);
    }
}
