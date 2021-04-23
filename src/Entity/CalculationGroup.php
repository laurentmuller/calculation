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
 * Represents a group of calculation categories.
 *
 * @author Laurent Muller
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationGroupRepository")
 * @ORM\Table(name="sy_CalculationGroup")
 */
class CalculationGroup extends AbstractEntity implements \Countable
{
    /**
     * The total amount.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    protected float $amount = 0.0;

    /**
     * The parent's calculation.
     *
     * @ORM\ManyToOne(targetEntity=Calculation::class, inversedBy="groups")
     * @ORM\JoinColumn(name="calculation_id", onDelete="CASCADE", nullable=false)
     */
    protected ?Calculation $calculation = null;

    /**
     * The calculation items.
     *
     * @ORM\OneToMany(targetEntity=CalculationCategory::class, mappedBy="group", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"code" = "ASC"})
     * @Assert\Valid
     *
     * @var Collection|CalculationCategory[]
     * @psalm-var Collection<int, CalculationCategory>
     */
    protected $categories;

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
     * @ORM\ManyToOne(targetEntity=Group::class)
     * @ORM\JoinColumn(name="group_id", nullable=false)
     */
    protected ?Group $group = null;

    /**
     * The margin in percent (%).
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    protected float $margin = 0.0;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        // clone categories
        $this->categories = $this->categories->map(function (CalculationCategory $category) {
            return (clone $category)->setGroup($this);
        });
    }

    /**
     * Add a category.
     */
    public function addCategory(CalculationCategory $category): self
    {
        if (!$this->contains($category)) {
            $this->categories[] = $category;
            $category->setGroup($this);
        }

        return $this;
    }

    /**
     * Checks whether the given category is contained within this collection of categories.
     *
     * @param CalculationCategory $category the item to search for
     *
     * @return bool true if this collection contains the category, false otherwise
     */
    public function contains(CalculationCategory $category): bool
    {
        return $this->categories->contains($category);
    }

    /**
     * {@inheritdoc}
     *
     * @return int the number of categories
     */
    public function count(): int
    {
        return $this->categories->count();
    }

    /**
     * Create a calculation group from the given group.
     *
     * @param Group $group the group to copy values from
     */
    public static function create(Group $group): self
    {
        $created = new self();

        return $created->setGroup($group);
    }

    /**
     * Get the total amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the parent's calculation.
     */
    public function getCalculation(): ?Calculation
    {
        return $this->calculation;
    }

    /**
     * Get the calculation categories.
     *
     * @return Collection|CalculationCategory[]
     * @psalm-return Collection<int, CalculationCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * Get the code.
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
     * Get the group.
     */
    public function getGroup(): ?Group
    {
        return $this->group;
    }

    /**
     * Get the margin.
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
     * Gets the total.
     *
     * This is the sum of the amount and the margin amount.
     */
    public function getTotal(): float
    {
        return $this->amount * $this->margin;
    }

    /**
     * Checks whether the categories is empty (contains no categories).
     *
     * @return bool true if the groups is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return $this->categories->isEmpty();
    }

    /**
     * Returns if this group is sortable.
     *
     * @return bool true if sortable; false otherwise
     */
    public function isSortable(): bool
    {
        foreach ($this->categories as $category) {
            if ($category->isSortable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a category.
     *
     * @param CalculationCategory $category the item to remove
     */
    public function removeCategory(CalculationCategory $category): self
    {
        if ($this->categories->removeElement($category)) {
            if ($category->getGroup() === $this) {
                $category->setGroup(null);
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
     * Set the parent's calculation.
     */
    public function setCalculation(?Calculation $calculation): self
    {
        $this->calculation = $calculation;

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
     * Set the group.
     *
     * @param Group $group  the group to copy values from
     * @param bool  $update true to update the total amount and the margin
     */
    public function setGroup(Group $group, $update = false): self
    {
        // copy
        $this->group = $group;
        $this->code = $group->getCode();

        if ($update) {
            return $this->update();
        }

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

        $iterator = $this->categories->getIterator();

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
     * @param CalculationGroup $other the other item to swap identifier for
     */
    public function swapIds(self $other): void
    {
        $oldId = $this->id;
        $this->id = $other->id;
        $other->id = $oldId;
    }

    /**
     * Update the total amount, the margin and this categories.
     */
    public function update(): self
    {
        // update categories
        $amount = 0;
        foreach ($this->categories as $category) {
            $category->update()->setGroup($this);
            $amount += $category->getAmount();
        }

        // margin
        $margin = $this->group->findPercent($amount);

        return $this->setAmount($amount)->setMargin($margin);
    }

    /**
     * Sorts categories of the given iterator.
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
        $iterator->uasort(function (CalculationCategory $a, CalculationCategory $b) use (&$changed): void {
            $result = \strcasecmp($a->getCode(), $b->getCode());
            if ($result > 0) {
                $b->swapIds($a);
                $changed = true;
            }
        });

        return $changed;
    }
}
