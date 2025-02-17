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

namespace App\Entity;

use App\Interfaces\ComparableInterface;
use App\Interfaces\ParentTimestampableInterface;
use App\Interfaces\PositionInterface;
use App\Interfaces\SortModeInterface;
use App\Repository\TaskItemRepository;
use App\Traits\PositionTrait;
use App\Traits\ValidateMarginsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an item of a task.
 *
 * @implements ParentTimestampableInterface<Task>
 * @implements ComparableInterface<TaskItem>
 */
#[ORM\Table(name: 'sy_TaskItem')]
#[ORM\Entity(repositoryClass: TaskItemRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_task_item_task_name', columns: ['task_id', 'name'])]
#[UniqueEntity(fields: ['task', 'name'], message: 'task_item.unique_name', errorPath: 'name')]
class TaskItem extends AbstractEntity implements \Countable, ComparableInterface, ParentTimestampableInterface, PositionInterface
{
    use PositionTrait;
    /**
     * @use ValidateMarginsTrait<int, TaskItemMargin>
     */
    use ValidateMarginsTrait;

    /**
     * The margins.
     *
     * @var Collection<int, TaskItemMargin>
     *
     * @psalm-var ArrayCollection<int, TaskItemMargin>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: TaskItemMargin::class,
        mappedBy: 'taskItem',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['minimum' => SortModeInterface::SORT_ASC])]
    private Collection $margins;

    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    private ?string $name = null;

    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'task_id', nullable: false)]
    private ?Task $task = null;

    public function __construct()
    {
        $this->margins = new ArrayCollection();
    }

    #[\Override]
    public function __clone()
    {
        parent::__clone();
        $this->margins = $this->margins->map(
            fn (TaskItemMargin $margin): TaskItemMargin => (clone $margin)->setTaskItem($this)
        );
    }

    /**
     * Adds a margin.
     */
    public function addMargin(TaskItemMargin $margin): self
    {
        if (!$this->margins->contains($margin)) {
            $this->margins[] = $margin;
            $margin->setTaskItem($this);
        }

        return $this;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->getName(), (string) $other->getName());
    }

    /**
     * Gets the number of margins.
     *
     * @return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return $this->margins->count();
    }

    /**
     * Gets the margin for the given quantity.
     */
    public function findMargin(float $quantity): ?TaskItemMargin
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         *
         * @psalm-var TaskItemMargin|null
         */
        return $this->margins->findFirst(
            fn (int $key, TaskItemMargin $margin): bool => $margin->contains($quantity)
        );
    }

    /**
     * Finds the item margin value for the given quantity.
     *
     * @param float $quantity the quantity to get value for
     *
     * @return float the value of the item margin, if found; 0 otherwise
     *
     * @see TaskItem::findMargin()
     */
    public function findValue(float $quantity): float
    {
        $margin = $this->findMargin($quantity);

        return $margin instanceof TaskItemMargin ? $margin->getValue() : 0;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->name;
    }

    /**
     * @return Collection<int, TaskItemMargin>
     */
    #[\Override]
    public function getMargins(): Collection
    {
        return $this->margins;
    }

    /**
     * Gets the name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    #[\Override]
    public function getParentEntity(): ?Task
    {
        return $this->task;
    }

    /**
     * Returns if the task item does not contain margins.
     */
    public function isEmpty(): bool
    {
        return $this->margins->isEmpty();
    }

    /**
     * Remove a margin.
     *
     * @psalm-api
     */
    public function removeMargin(TaskItemMargin $margin): self
    {
        if ($this->margins->removeElement($margin) && $margin->getTaskItem() === $this) {
            $margin->setTaskItem(null);
        }

        return $this;
    }

    /**
     * Sets the name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the parent's task.
     */
    public function setTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }
}
