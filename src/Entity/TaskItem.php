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

use App\Repository\TaskItemRepository;
use App\Traits\PositionTrait;
use App\Traits\ValidateMarginsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an item of a task.
 */
#[ORM\Entity(repositoryClass: TaskItemRepository::class)]
#[ORM\Table(name: 'sy_TaskItem')]
#[ORM\UniqueConstraint(name: 'unique_task_item_task_name', columns: ['task_id', 'name'])]
#[UniqueEntity(fields: ['task', 'name'], message: 'task_item.unique_name', errorPath: 'name')]
class TaskItem extends AbstractEntity implements \Countable
{
    use PositionTrait;
    use ValidateMarginsTrait;

    /**
     * The margins.
     *
     * @var Collection<int, TaskItemMargin>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'taskItem', targetEntity: TaskItemMargin::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['minimum' => Criteria::ASC])]
    private Collection $margins;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'task_id', nullable: false)]
    private ?Task $task = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->margins = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        // clone margins
        $this->margins = $this->margins->map(fn (TaskItemMargin $margin) => (clone $margin)->setTaskItem($this));
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

    /**
     * {@inheritdoc}
     *
     * @return int the number of margins
     */
    public function count(): int
    {
        return $this->margins->count();
    }

    /**
     * Gets the margin for the given quantity.
     */
    public function findMargin(float $quantity): ?TaskItemMargin
    {
        foreach ($this->margins as $margin) {
            if ($margin->contains($quantity)) {
                return $margin;
            }
        }

        return null;
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

        return null !== $margin ? $margin->getValue() : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return (string) $this->name;
    }

    /**
     * @return Collection<int, TaskItemMargin>
     */
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

    /**
     * Gets the parent's task.
     */
    public function getTask(): ?Task
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
