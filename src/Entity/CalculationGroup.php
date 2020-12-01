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
class CalculationGroup extends AbstractEntity
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
     * The calculation items.
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
        $this->amount = 0.0;
        $this->margin = 0.0;
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
        if (!$this->contains($item)) {
            $this->items->add($item);
            $item->setGroup($this);
        }

        return $this;
    }

    /**
     * Checks whether the given item is contained within this collection of items.
     *
     * @param CalculationItem $item the item to search for
     *
     * @return bool true if this collection contains the item, false otherwise
     */
    public function contains(CalculationItem $item): bool
    {
        return $this->items->contains($item);
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
        return $this->amount * ($this->margin - 1);
    }

    /**
     * Gets the parent category.
     */
    public function getParentCategory(): ?Category
    {
        if (null !== $this->category) {
            return $this->category->getParent();
        }

        return null;
    }

    /**
     * Gets the parent category code.
     *
     * @param string $default the default value to use if the parent code is null
     */
    public function getParentCode(string $default = null): ?string
    {
        $category = $this->getParentCategory();
        if (null !== $category) {
            return $category->getCode();
        }

        return $default;
    }

    /**
     * Gets the total.
     * This is the sum of the amount and the margin amount.
     */
    public function getTotal(): float
    {
        return $this->amount * $this->margin;
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
     * Returns a value indicating if this group is a root group.
     */
    public function isRootGroup(): bool
    {
        return null === $this->getParentCategory();
    }

    /**
     * Returns if this group is sortable.
     *
     * @return bool true if sortable; false otherwise
     */
    public function isSortable(): bool
    {
        return $this->items->count() > 1;
    }

    /**
     * Remove an item.
     *
     * @param CalculationItem $item the item to remove
     */
    public function removeItem(CalculationItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getGroup() === $this) {
                $item->setGroup(null);
            }
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
     * @param bool                 $update   true to update the amount and the margin
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
     *
     * This property is present only for the form builder.
     *
     * @param int $categoryId
     * @psalm-suppress UnusedParam
     */
    public function setCategoryId(?int $categoryId): self
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
     * Sorts this items by the alphabetical order of descriptions.
     *
     * @return bool true if the order has changed
     */
    public function sort(): bool
    {
        // items?
        if (!$this->isSortable()) {
            return false;
        }

        /** @var \ArrayIterator $iterator */
        $iterator = $this->items->getIterator();

        // first sort
        $changed = $this->sortItemsIterator($iterator);

        // sort until no change found
        if ($changed) {
            do {
                $dirty = $this->sortItemsIterator($iterator);
            } while ($dirty);
        }

        return $changed;
    }

    /**
     * Swaps the identifiers.
     *
     * @param CalculationGroup $other the other item to swap identifier for
     */
    public function swapIds(self $other): void
    {
        $oldId = $this->id;
        $this->id = $other->id;
        $other->id = $oldId;
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

    /**
     * Sorts items of the given iterator.
     *
     * @param mixed $iterator the iterator to sort
     *
     * @return bool true if sort changed the order
     *
     * @see \ArrayIterator::uasort
     */
    private function sortItemsIterator($iterator): bool
    {
        $changed = false;
        $iterator->uasort(function (CalculationItem $a, CalculationItem $b) use (&$changed): void {
            $result = \strcasecmp($a->getDescription(), $b->getDescription());
            if ($result > 0) {
                $b->swapValues($a);
                $changed = true;
            }
        });

        return $changed;
    }
}
