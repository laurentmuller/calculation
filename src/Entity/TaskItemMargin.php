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

use App\Interfaces\MarginInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a margin of a task item.
 *
 * @ORM\Table(name="sy_TaskItemMargin")
 * @ORM\Entity(repositoryClass="App\Repository\TaskItemMarginRepository")
 */
class TaskItemMargin extends AbstractEntity implements MarginInterface
{
    /**
     * The maximum quantity (exclusive) to apply within this value.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    #[Assert\Type(type: 'float')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\GreaterThan(propertyPath: 'minimum', message: 'margin.maximum_greater_minimum')]
    private float $maximum = 0.0;

    /**
     * The minimum quantity (inclusive) to apply within this value.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    #[Assert\Type(type: 'float')]
    #[Assert\GreaterThanOrEqual(0)]
    private float $minimum = 0.0;

    /**
     * The parent task item.
     *
     * @ORM\ManyToOne(targetEntity=TaskItem::class, inversedBy="margins")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?TaskItem $taskItem = null;

    /**
     * The value to use when a quantity is within this range.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    #[Assert\Type(type: 'float')]
    #[Assert\GreaterThanOrEqual(0)]
    private float $value = 0.0;

    /**
     * {@inheritdoc}
     */
    public function contains(float $value): bool
    {
        return $value >= $this->minimum && $value < $this->maximum;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaximum(): float
    {
        return $this->maximum;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinimum(): float
    {
        return $this->minimum;
    }

    /**
     * Gets the parent task item.
     */
    public function getTaskItem(): ?TaskItem
    {
        return $this->taskItem;
    }

    /**
     * Gets the value (unit price).
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Sets the maximum.
     */
    public function setMaximum(float $maximum): self
    {
        $this->maximum = $maximum;

        return $this;
    }

    /**
     * Sets the minimum.
     */
    public function setMinimum(float $minimum): self
    {
        $this->minimum = $minimum;

        return $this;
    }

    /**
     * Sets the parent task item.
     */
    public function setTaskItem(?TaskItem $taskItem): self
    {
        $this->taskItem = $taskItem;

        return $this;
    }

    /**
     * Sets the value (unit price).
     */
    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }
}
