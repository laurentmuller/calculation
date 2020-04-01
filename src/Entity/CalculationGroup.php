<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a group of products.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationGroupRepository")
 * @ORM\Table(name="sy_CalculationGroup")
 */
class CalculationGroup extends BaseEntity
{
    /**
     * The total amount.
     *
     * @ORM\Column(name="amount", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $amount;

    /**
     * The parent's calculation.
     *
     * @ORM\ManyToOne(
     *     targetEntity="Calculation",
     *     inversedBy="groups"
     * )
     *
     * @ORM\JoinColumn(
     *     name="calculation_id",
     *     referencedColumnName="id",
     *     onDelete="CASCADE",
     *     nullable=false
     * )
     *
     * @var \App\Entity\Calculation
     */
    protected $calculation;

    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(
     *     name="category_id",
     *     referencedColumnName="id",
     *     nullable=false
     * )
     *
     * @var \App\Entity\Category
     */
    protected $category;

    /**
     * The code.
     *
     * @ORM\Column(name="code", type="string", length=30)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     *
     * @var string
     */
    protected $code;

    /**
     * The product items.
     *
     * @ORM\OneToMany(
     *     targetEntity="CalculationItem",
     *     mappedBy="group",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     *
     * @Assert\Valid
     *
     * @var Collection|CalculationItem[]
     */
    protected $items;

    /**
     * The margin in percent (%).
     *
     * @ORM\Column(name="margin", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $margin;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // items
        $this->items = new ArrayCollection();

        // default values
        $this->amount = $this->margin = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        // clone items
        $this->items = $this->items->map(function (CalculationItem $item) {
            return (clone $item)->setGroup($this);
        });
    }

    /**
     * Add an item.
     *
     * @param CalculationItem $item the item to add
     */
    public function addItem(CalculationItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setGroup($this);
        }

        return $this;
    }

    /**
     * Create a calculation group from the given category.
     *
     * @param Category $category the category to copy values from
     */
    public static function create(Category $category): self
    {
        $group = new self();

        return $group->setCategory($category);
    }

    /**
     * Get amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get calculation.
     *
     * @return \App\Entity\Calculation
     */
    public function getCalculation(): ?Calculation
    {
        return $this->calculation;
    }

    /**
     * Get category.
     *
     * @return \App\Entity\Category
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Get category id.
     * This property is created only for the form builder.
     *
     * @return int
     */
    public function getCategoryId(): ?int
    {
        if (null !== $this->category) {
            return $this->category->getId();
        }

        return null;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return $this->getCode();
    }

    /**
     * Get calculation items.
     *
     * @return Collection|CalculationItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Get margin.
     */
    public function getMargin(): float
    {
        return $this->margin;
    }

    /**
     * Gets the margin amount.
     */
    public function getMarginAmount(): float
    {
        return $this->amount * $this->margin;
    }

    /**
     * Gets the total.
     * This is the sum of the amount and the margin amount.
     */
    public function getTotal(): float
    {
        return $this->amount * (1 + $this->margin);
    }

    /**
     * Checks whether the groups is empty (contains no elements).
     *
     * @return bool TRUE if the groups is empty, FALSE otherwise
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Remove an item.
     *
     * @param CalculationItem $item the item to remove
     */
    public function removeItem(CalculationItem $item): self
    {
        if ($this->items->contains($item) && $this->items->removeElement($item)) {
            $item->setGroup(null);
        }

        return $this;
    }

    /**
     * Set amount.
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $this->round($amount);

        return $this;
    }

    /**
     * Set calculation.
     *
     * @param \App\Entity\Calculation $calculation
     */
    public function setCalculation(?Calculation $calculation): self
    {
        $this->calculation = $calculation;

        return $this;
    }

    /**
     * Set category.
     *
     * @param \App\Entity\Category $category the category to copy values from
     * @param bool                 $update   true to copy the code, the description and update the amount and the margin
     */
    public function setCategory(Category $category, $update = false): self
    {
        // copy
        $this->category = $category;
        $this->code = $category->getCode();

        if ($update) {
            return $this->update();
        }

        return $this;
    }

    /**
     * Set category id.
     * This property is created only for the form builder.
     *
     * @param int $categoryId
     */
    public function setCategoryId($categoryId): self
    {
        return $this;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode(?string $code): self
    {
        $this->code = $this->trim($code);

        return $this;
    }

    /**
     * Set margin.
     */
    public function setMargin(float $margin): self
    {
        $this->margin = $this->round($margin);

        return $this;
    }

    /**
     * Update amount and margin for this group of items.
     */
    public function update(): self
    {
        // update items
        $amount = 0;
        foreach ($this->items as $item) {
            $item->setGroup($this);
            $amount += $item->getTotal();
        }

        // margin
        $margin = $this->category->findPercent($amount);

        return $this->setAmount($amount)->setMargin($margin);
    }
}
