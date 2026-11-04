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

use App\Doctrine\FixedFloatType;
use App\Interfaces\MarginInterface;
use App\Interfaces\ParentTimestampableInterface;
use App\Repository\TaskItemMarginRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a margin of a task item.
 *
 * @implements ParentTimestampableInterface<Task>
 */
#[ORM\Table(name: 'sy_TaskItemMargin')]
#[ORM\Entity(repositoryClass: TaskItemMarginRepository::class)]
class TaskItemMargin extends AbstractEntity implements MarginInterface, ParentTimestampableInterface
{
    /** The maximum quantity (exclusive) to apply within this value. */
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\GreaterThan(propertyPath: 'minimum', message: 'margin.maximum_greater_minimum')]
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $maximum = 0.0 {
        get => $this->maximum;
        set => $this->round($value);
    }

    /** The minimum quantity (inclusive) to apply within this value. */
    #[Assert\GreaterThanOrEqual(0)]
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $minimum = 0.0 {
        get => $this->minimum;
        set => $this->round($value);
    }

    /** The parent task item. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'margins')]
    #[ORM\JoinColumn(name: 'task_item_id', nullable: false)]
    public ?TaskItem $taskItem = null;

    /** The value to use when a quantity is within this range. */
    #[Assert\GreaterThanOrEqual(0)]
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $value = 0.0 {
        set => $this->round($value);
    }

    #[\Override]
    public function contains(float $value): bool
    {
        return $value >= $this->minimum && $value < $this->maximum;
    }

    #[\Override]
    public function getParentEntity(): ?Task
    {
        return $this->taskItem?->getParentEntity();
    }

    /**
     * Sets the maximum.
     */
    public function withMaximum(float $maximum): self
    {
        $this->maximum = $maximum;

        return $this;
    }

    /**
     * Sets the minimum.
     */
    public function withMinimum(float $minimum): self
    {
        $this->minimum = $minimum;

        return $this;
    }

    /**
     * Sets the parent task item.
     */
    public function withTaskItem(?TaskItem $taskItem): self
    {
        $this->taskItem = $taskItem;

        return $this;
    }

    /**
     * Sets the value (unit price).
     */
    public function withValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }
}
