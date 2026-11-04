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
use App\Repository\TaskItemRepository;
use App\Traits\PositionTrait;
use App\Traits\ValidateMarginsTrait;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an item of a task.
 *
 * @implements ComparableInterface<TaskItem>
 * @implements ParentTimestampableInterface<Task>
 */
#[ORM\Table(name: 'sy_TaskItem')]
#[ORM\Entity(repositoryClass: TaskItemRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_task_item_task_name', columns: ['task_id', 'name'])]
#[UniqueEntity(fields: ['task', 'name'], message: 'task_item.unique_name', errorPath: 'name')]
class TaskItem extends AbstractEntity implements \Countable, ComparableInterface, ParentTimestampableInterface, PositionInterface
{
    use PositionTrait;

    /** @use ValidateMarginsTrait<TaskItemMargin> */
    use ValidateMarginsTrait;

    /**
     * The margins.
     *
     * @var Collection<int, TaskItemMargin>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: TaskItemMargin::class,
        mappedBy: 'taskItem',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['minimum' => \SortDirection::Ascending])]
    public Collection $margins {
        get => $this->margins;
    }

    /** The name. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    public ?string $name = null {
        set => StringUtils::trim($value);
    }

    /** The parent's task. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'task_id', nullable: false)]
    public ?Task $task = null;

    public function __construct()
    {
        $this->margins = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->margins = $this->margins->map(
            fn (TaskItemMargin $margin): TaskItemMargin => (clone $margin)->withTaskItem($this)
        );
    }

    /**
     * Adds a margin.
     */
    public function addMargin(TaskItemMargin $margin): self
    {
        if (!$this->margins->contains($margin)) {
            $this->margins->add($margin);
            $margin->withTaskItem($this);
        }

        return $this;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->name, (string) $other->name);
    }

    /**
     * Gets the number of margins.
     */
    #[\Override]
    public function count(): int
    {
        return $this->margins->count();
    }

    /**
     * Finds the margin for the given quantity.
     *
     * @param float $quantity the quantity to get margin for
     *
     * @return ?TaskItemMargin the margin, if found; null otherwise
     */
    public function findMargin(float $quantity): ?TaskItemMargin
    {
        return $this->margins->findFirst(
            static fn (int $key, TaskItemMargin $margin): bool => $margin->contains($quantity)
        );
    }

    /**
     * Finds the item margin value for the given quantity.
     *
     * @param float $quantity the quantity to get the value for
     *
     * @return float the value of the item margin, if found; 0 otherwise
     *
     * @see TaskItem::findMargin()
     */
    public function findValue(float $quantity): float
    {
        return $this->findMargin($quantity)->value ?? 0.0;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->name;
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
     */
    public function removeMargin(TaskItemMargin $margin): self
    {
        if ($this->margins->removeElement($margin) && $margin->taskItem === $this) {
            $margin->withTaskItem(null);
        }

        return $this;
    }

    /**
     * Sets the name.
     */
    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the parent's task.
     */
    public function withTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }
}
