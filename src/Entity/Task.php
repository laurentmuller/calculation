<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent a Task.
 *
 * @author Laurent Muller
 *
 * @ORM\Table(name="sy_Task")
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 * @UniqueEntity(fields="name", message="task.unique_name")
 */
class Task extends AbstractCategoryItemEntity implements \Countable
{
    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="tasks")
     * @ORM\JoinColumn(name="category_id", nullable=false)
     * @Assert\NotNull
     */
    protected ?Category $category = null;

    /**
     * The task items.
     *
     * @ORM\OneToMany(targetEntity=TaskItem::class, mappedBy="task", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     * @ORM\OrderBy({"position" = "ASC"})
     *
     * @var Collection|TaskItem[]
     * @psalm-var Collection<int, TaskItem>
     */
    private Collection $items;

    /**
     * The name.
     *
     * @ORM\Column(type="string", length=255)
     */
    private ?string $name = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        // clone items
        $this->items = $this->items->map(function (TaskItem $item) {
            return (clone $item)->setTask($this);
        });
    }

    /**
     * Add a task item.
     */
    public function addItem(TaskItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setTask($this);
        }

        return $this;
    }

    /**
     * Clone this task.
     *
     * @param string   $name     the new name
     * @param Category $category the default category
     */
    public function clone(?string $name = null, ?Category $category = null): self
    {
        /** @var Task $copy */
        $copy = clone $this;

        // copy default values
        if (null !== $name) {
            $copy->setName($name);
        }
        if (null !== $category) {
            $copy->setCategory($category);
        }

        return $copy;
    }

    /**
     * {@inheritdoc}
     *
     * @return int the number of items
     */
    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * Gets the number of margins in all items.
     */
    public function countMargins(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->count();
        }

        return $count;
    }

    /**
     * Returns all the items that satisfy the predicate p.
     *
     * @param \Closure $p the predicate used for filtering
     *
     * @return Collection|TaskItem[] the collection with the results of the filter operation
     * @psalm-return Collection<int, TaskItem>
     */
    public function filter(\Closure $p): Collection
    {
        return $this->items->filter($p);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return (string) $this->name;
    }

    /**
     * @return Collection|TaskItem[]
     * @psalm-return Collection<int, TaskItem>
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
            if ($item->getTask() === $this) {
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
     * Update position of items.
     */
    public function updatePositions(): self
    {
        $position = 0;

        /** @var TaskItem $item */
        foreach ($this->items as $item) {
            if ($item->getPosition() !== $position) {
                $item->setPosition($position);
            }
            ++$position;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->name,
            $this->unit,
            $this->getCategoryCode(),
            $this->getGroupCode(),
        ];
    }
}
