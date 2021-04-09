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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Task item.
 *
 * @author Laurent Muller
 *
 * @ORM\Table(name="sy_TaskItem")
 * @ORM\Entity(repositoryClass="App\Repository\TaskItemRepository")
 * @UniqueEntity(fields={"task", "name"}, message="task_item.unique_name", errorPath="name")
 */
class TaskItem extends AbstractEntity implements \Countable
{
    /**
     * @ORM\OneToMany(targetEntity=TaskItemMargin::class, mappedBy="taskItem", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"minimum" = "ASC"})
     * @Assert\Valid
     *
     * @var Collection|TaskItemMargin[]
     * @psalm-var Collection<int, TaskItemMargin>
     */
    private $margins;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Task::class, inversedBy="items")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Task
     */
    private $task;

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
     * @return Collection|TaskItemMargin[]
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
        if ($this->margins->removeElement($margin)) {
            if ($margin->getTaskItem() === $this) {
                $margin->setTaskItem(null);
            }
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
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context): void
    {
        // margins?
        $margins = $this->getMargins();
        if ($margins->isEmpty()) {
            return;
        }

        $lastMin = null;
        $lastMax = null;

        /** @var TaskItemMargin $margin */
        foreach ($margins as $key => $margin) {
            // get values
            $min = $margin->getMinimum();
            $max = $margin->getMaximum();

            if (null === $lastMin) {
                // first time
                $lastMin = $min;
                $lastMax = $max;
            } elseif ($min <= $lastMin) {
                // the minimum is smaller than the previous maximum
                $context->buildViolation('task_item.minimum_overlap')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($min >= $lastMin && $min < $lastMax) {
                // the minimum is overlapping the previous margin
                $context->buildViolation('task_item.minimum_overlap')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($max > $lastMin && $max < $lastMax) {
                // the maximum is overlapping the previous margin
                $context->buildViolation('task_item.maximum_overlap')
                    ->atPath("margins[$key].maximum")
                    ->addViolation();
                break;
            } elseif ($min !== $lastMax) {
                // the minimum is not equal to the previous maximum
                $context->buildViolation('task_item.minimum_discontinued')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } else {
                // copy
                $lastMin = $min;
                $lastMax = $max;
            }
        }
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
