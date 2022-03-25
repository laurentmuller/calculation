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

use App\Traits\PositionTrait;
use App\Traits\ValidateMarginsTraits;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an item of a task.
 *
 * @author Laurent Muller
 *
 * @ORM\Table(name="sy_TaskItem")
 * @ORM\Entity(repositoryClass="App\Repository\TaskItemRepository")
 * @UniqueEntity(fields={"task", "name"}, message="task_item.unique_name", errorPath="name")
 */
class TaskItem extends AbstractEntity implements \Countable
{
    use PositionTrait;
    use ValidateMarginsTraits;

    /**
     * @ORM\OneToMany(targetEntity=TaskItemMargin::class, mappedBy="taskItem", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"minimum" = "ASC"})
     * @Assert\Valid
     *
     * @var TaskItemMargin[]|Collection
     * @psalm-var Collection<int, TaskItemMargin>
     */
    private Collection $margins;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity=Task::class, inversedBy="items")
     * @ORM\JoinColumn(nullable=false)
     */
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
        $this->margins = $this->margins->map(function (TaskItemMargin $margin) {
            return (clone $margin)->setTaskItem($this);
        });
    }

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
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return (string) $this->name;
    }

    /**
     * Gets the margin for the given quantity.
     */
    public function getMargin(float $quantity): ?TaskItemMargin
    {
        foreach ($this->margins as $margin) {
            if ($margin->contains($quantity)) {
                return $margin;
            }
        }

        return null;
    }

    /**
     * @return TaskItemMargin[]|Collection
     * @psalm-return Collection<int, TaskItemMargin>
     */
    public function getMargins(): Collection
    {
        return $this->margins;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

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

    public function removeMargin(TaskItemMargin $margin): self
    {
        if ($this->margins->removeElement($margin) && $margin->getTaskItem() === $this) {
            $margin->setTaskItem(null);
        }

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->name,
        ];
    }
}
