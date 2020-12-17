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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sy_TaskItemMargin")
 * @ORM\Entity(repositoryClass="App\Repository\TaskItemMarginRepository")
 */
class TaskItemMargin extends AbstractEntity
{
    /**
     * @ORM\Column(type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    private $maximum;

    /**
     * @ORM\Column(type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    private $minimum;

    /**
     * @ORM\ManyToOne(targetEntity=TaskItem::class, inversedBy="margins")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var TaskItem
     */
    private $taskItem;

    /**
     * @ORM\Column(type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    private $value;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->minimum = $this->maximum = $this->value = 0;
    }

    /**
     * Checks if this margin is between this minimum (inclusive) and this maximum (exlcusive) quantity.
     *
     * @param float $quantity the quantity to verify
     *
     * @return bool true if within this range
     */
    public function contains(float $quantity): bool
    {
        return $quantity >= $this->minimum && $quantity <= $this->maximum;
    }

    public function getMaximum(): float
    {
        return $this->maximum;
    }

    public function getMinimum(): float
    {
        return $this->minimum;
    }

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

    public function setMaximum(float $maximum): self
    {
        $this->maximum = $maximum;

        return $this;
    }

    public function setMinimum(float $minimum): self
    {
        $this->minimum = $minimum;

        return $this;
    }

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
