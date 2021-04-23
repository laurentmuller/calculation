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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a category of calculation items.
 *
 * @author Laurent Muller
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationCategoryRepository")
 * @ORM\Table(name="sy_CalculationCategory")
 */
class CalculationCategory extends AbstractEntity implements \Countable
{
    /**
     * The total amount.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    protected float $amount = 0.0;

    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(name="category_id", nullable=false)
     */
    protected ?Category $category = null;

    /**
     * The code.
     *
     * @ORM\Column(type="string", length=30)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     */
    protected ?string $code = null;

    /**
     * The parent's group.
     *
     * @ORM\ManyToOne(targetEntity=CalculationGroup::class, inversedBy="categories")
     * @ORM\JoinColumn(name="group_id", onDelete="CASCADE", nullable=false)
     */
    protected ?CalculationGroup $group = null;

    /**
     * The calculation items.
     *
     * @ORM\OneToMany(targetEntity=CalculationItem::class, mappedBy="category", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @var Collection|CalculationItem[]
     * @psalm-var Collection<int, CalculationItem>
     */
    protected $items;

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
        $this->items = $this->items->map(function (CalculationItem $item) {
            return (clone $item)->setCategory($this);
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
            $this->items[] = $item;
            $item->setCategory($this);
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
     * {@inheritdoc}
     *
     * @return int the number of items
     */
    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * Create a calculation category from the given category.
     *
     * @param Category $category the category to copy values from
     */
    public static function create(Category $category): self
    {
        $created = new self();

        return $created->setCategory($category);
    }

    /**
     * Get the total amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the calculation.
     */
    public function getCalculation(): ?Calculation
    {
        return $this->group ? $this->group->getCalculation() : null;
    }

    /**
     * Get the category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
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
     * Get the parent's group.
     */
    public function getGroup(): ?CalculationGroup
    {
        return $this->group;
    }

    /**
     * Get calculation items.
     *
     * @return Collection|CalculationItem[]
     * @psalm-return Collection<int, CalculationItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Checks whether the category is empty (contains no items).
     *
     * @return bool true if the groups is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Returns if this group is sortable.
     *
     * @return bool true if sortable; false otherwise
     */
    public function isSortable(): bool
    {
        return $this->count() > 1;
    }

    /**
     * Remove an item.
     *
     * @param CalculationItem $item the item to remove
     */
    public function removeItem(CalculationItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCategory() === $this) {
                $item->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * Set the total amount.
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $this->round($amount);

        return $this;
    }

    /**
     * Set the category.
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
     * Set the code.
     */
    public function setCode(?string $code): self
    {
        $this->code = $this->trim($code);

        return $this;
    }

    /**
     * Set the parent's group.
     */
    public function setGroup(?CalculationGroup $group): self
    {
        $this->group = $group;

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

        $iterator = $this->items->getIterator();

        // first sort
        $changed = $this->sortIterator($iterator);

        // sort until no change found
        if ($changed) {
            do {
                $dirty = $this->sortIterator($iterator);
            } while ($dirty);
        }

        return $changed;
    }

    /**
     * Swaps the identifiers.
     *
     * @param CalculationCategory $other the other item to swap identifier for
     */
    public function swapIds(self $other): void
    {
        $oldId = $this->id;
        $this->id = $other->id;
        $other->id = $oldId;
    }

    /**
     * Update the total amount and this items.
     */
    public function update(): self
    {
        // update items
        $amount = 0;
        foreach ($this->items as $item) {
            $item->setCategory($this);
            $amount += $item->getTotal();
        }

        return $this->setAmount($amount);
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
    private function sortIterator($iterator): bool
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
