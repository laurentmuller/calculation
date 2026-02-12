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
use App\Interfaces\SortModeInterface;
use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a task.
 *
 * @implements ComparableInterface<Task>
 */
#[ORM\Table(name: 'sy_Task')]
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_task_name', columns: ['name'])]
#[UniqueEntity(fields: 'name', message: 'task.unique_name')]
class Task extends AbstractCategoryItemEntity implements \Countable, ComparableInterface
{
    /** The parent's category. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    protected ?Category $category = null;

    /**
     * The children's items.
     *
     * @var Collection<int, TaskItem>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: TaskItem::class,
        mappedBy: 'task',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => SortModeInterface::SORT_ASC])]
    private Collection $items;

    /** The name. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    private ?string $name = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->items = $this->items->map(
            fn (TaskItem $item): TaskItem => (clone $item)->setTask($this)
        );
    }

    /**
     * Add a task item.
     */
    public function addItem(TaskItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setTask($this);
        }

        return $this;
    }

    /**
     * Clone this task.
     *
     * @param ?string $name the new name
     */
    public function clone(?string $name = null): self
    {
        $copy = clone $this;
        if (null !== $name) {
            $copy->setName($name);
        }

        return $copy;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->getName(), (string) $other->getName());
    }

    /**
     * Gets the number of items.
     *
     * @return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * Gets the number of margins for all items.
     */
    public function countMargins(): int
    {
        return $this->items->reduce(static fn (int $carry, TaskItem $item): int => $carry + $item->count(), 0);
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->name;
    }

    /**
     * Gets item's identifiers.
     *
     * @return int[]
     */
    public function getIdentifiers(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        return $this->items->map(static fn (TaskItem $item): int => (int) $item->getId())->toArray();
    }

    /**
     * @return Collection<int, TaskItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Returns if the task does not contain items.
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Remove the given item.
     */
    public function removeItem(TaskItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getParentEntity() === $this) {
                $item->setTask(null);
            }

            return $this->updatePositions();
        }

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Update the position of items.
     */
    public function updatePositions(): self
    {
        $position = 0;
        foreach ($this->items as $item) {
            $item->setPosition($position++);
        }

        return $this;
    }
}
