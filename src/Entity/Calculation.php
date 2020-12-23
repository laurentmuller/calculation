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

use App\Interfaces\TimestampableInterface;
use App\Traits\TimestampableTrait;
use App\Util\FormatUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationRepository")
 * @ORM\Table(name="sy_Calculation")
 */
class Calculation extends AbstractEntity implements TimestampableInterface
{
    use TimestampableTrait;

    /**
     * The customer name.
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $customer;

    /**
     * The calculation date.
     *
     * @ORM\Column(type="date")
     * @Assert\NotNull
     *
     * @var \DateTime
     */
    protected $date;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $description;

    /**
     * The global margin in percent (%).
     *
     * @ORM\Column(name="globalMargin", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $globalMargin;

    /**
     * The calculation groups.
     *
     * @ORM\OneToMany(targetEntity=CalculationGroup::class, mappedBy="calculation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"code": "ASC"})
     * @Assert\Valid
     *
     * @var Collection|CalculationGroup[]
     */
    protected $groups;

    /**
     * The total of all items.
     *
     * @ORM\Column(name="itemsTotal", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $itemsTotal;

    /**
     * The overall total.
     *
     * @ORM\Column(name="overallTotal", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $overallTotal;

    /**
     * The calculation state.
     *
     * @ORM\ManyToOne(targetEntity=CalculationState::class, inversedBy="calculations")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     *
     * @var ?CalculationState
     */
    protected $state;

    /**
     * The user margin in percent (%).
     *
     * @ORM\Column(name="userMargin", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $userMargin;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->date = new \DateTime();
        $this->globalMargin = 0.0;
        $this->userMargin = 0.0;
        $this->itemsTotal = 0.0;
        $this->overallTotal = 0.0;
        $this->groups = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        // reset dates
        $this->date = new \DateTime();
        $this->createdAt = $this->updatedAt = new \DateTimeImmutable();

        // clone groups
        $this->groups = $this->groups->map(function (CalculationGroup $group) {
            return (clone $group)->setCalculation($this);
        });
    }

    /**
     * Add a group.
     *
     * @param CalculationGroup $group the group to add
     */
    public function addGroup(CalculationGroup $group): self
    {
        if (!$this->contains($group)) {
            $this->groups[] = $group;
            $group->setCalculation($this);
        }

        return $this;
    }

    /**
     * Adds a product; creating the group and the category if needed.
     *
     * @param Product $product  the product to add
     * @param float   $quantity the product quantity
     */
    public function addProduct(Product $product, float $quantity = 1.0): self
    {
        $item = CalculationItem::create($product)->setQuantity($quantity);
        $category = $this->findCategory($product->getCategory());
        $category->addItem($item);
        $category->update();

        return $this;
    }

    /**
     * Clone this calculation.
     *
     * @param CalculationState $state the default state
     */
    public function clone(?CalculationState $state): self
    {
        /** @var Calculation $copy */
        $copy = clone $this;

        // copy default values
        if ($state) {
            $copy->setState($state);
        }

        return $copy;
    }

    /**
     * Checks whether the given group is contained within this collection of groups.
     *
     * @param CalculationGroup $group the group to search for
     *
     * @return bool true if this collection contains the group, false otherwise
     */
    public function contains(CalculationGroup $group): bool
    {
        return $this->groups->contains($group);
    }

    /**
     * Finds a calculation category for the given category.
     * <p>
     * <b>Note:</b> if the calculation category is not found, a new one is created.
     * </p>.
     *
     * @param Category $category the category to find
     */
    public function findCategory(Category $category): CalculationCategory
    {
        // find group
        $group = $this->findGroup($category->getGroup());

        // find category
        $code = $category->getCode();
        foreach ($group->getCategories() as $current) {
            if ($code === $current->getCode()) {
                return $current;
            }
        }

        // create category
        $newCategory = CalculationCategory::create($category);
        $group->addCategory($newCategory);

        return $newCategory;
    }

    /**
     * Finds a calculation group for the given group.
     * <p>
     * <b>Note:</b> if the calculation group is not found, a new one is created.
     * </p>.
     *
     * @param Group $group the group to find
     */
    public function findGroup(Group $group): CalculationGroup
    {
        // find group
        $code = $group->getCode();
        foreach ($this->groups as $calculationGroup) {
            if ($code === $calculationGroup->getCode()) {
                return $calculationGroup;
            }
        }

        // create group
        $newGroup = CalculationGroup::create($group);
        $this->addGroup($newGroup);

        return $newGroup;
    }

    /**
     * Gets the number of categories.
     */
    public function getCategoriesCount(): int
    {
        $count = 0;
        foreach ($this->groups as $group) {
            $count += $group->getCategories()->count();
        }

        return $count;
    }

    /**
     * Get customer.
     *
     * @return string
     */
    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    /**
     * Get date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return FormatUtils::formatId($this->id);
    }

    /**
     * Gets the duplicates items.
     *
     * Items are duplicate when two or more item descriptions are equal, ignoring case considerations.
     *
     * @return CalculationItem[] an array, maybe empty of duplicate items
     *
     * @see Calculation::hasDuplicateItems()
     * @see Calculation::removeDuplicateItems()
     */
    public function getDuplicateItems(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        // group by key
        $array = [];
        foreach ($this->getItems() as $item) {
            $key = $this->getItemKey($item);
            $array[$key][] = $item;
        }

        // merge duplicated items
        return \array_reduce($array, function (array $current, array $items): array {
            if (\count($items) > 1) {
                return \array_merge($current, \array_values($items));
            }

            return $current;
        }, []);
    }

    /**
     * Gets the empty items.
     *
     * Items are empty if the price or the quantity is equal to 0.
     *
     * @return CalculationItem[] an array, maybe empty of empty  items
     *
     * @see Calculation::hasEmptyItems()
     * @see Calculation::removeEmptyItems()
     */
    public function getEmptyItems(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        $result = [];

        /** @var CalculationItem $item */
        foreach ($this->getItems() as $item) {
            if ($item->isEmpty()) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Get global margin.
     */
    public function getGlobalMargin(): float
    {
        return $this->globalMargin;
    }

    /**
     * Get global margin amount.
     */
    public function getGlobalMarginAmount(): float
    {
        return $this->getGroupsTotal() * $this->globalMargin;
    }

    /**
     * Get groups.
     *
     * @return CalculationGroup[]|Collection
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * Gets the total amount of all groups.
     */
    public function getGroupsAmount(): float
    {
        $total = 0;
        foreach ($this->groups as $group) {
            $total += $group->getAmount();
        }

        return $total;
    }

    /**
     * Gets the number of groups.
     */
    public function getGroupsCount(): int
    {
        return $this->groups->count();
    }

    /**
     * Gets the margin of all root groups.
     */
    public function getGroupsMargin(): float
    {
        $divisor = $this->getGroupsAmount();
        $dividend = $this->getGroupsMarginAmount();

        return 1.0 + $this->safeDivide($dividend, $divisor);
    }

    /**
     * Gets the total margin amount of all root groups.
     */
    public function getGroupsMarginAmount(): float
    {
        $total = 0;
        foreach ($this->groups as $group) {
            $total += $group->getMarginAmount();
        }

        return $total;
    }

    /**
     * Gets the total of all groups.
     */
    public function getGroupsTotal(): float
    {
        $total = 0;
        foreach ($this->groups as $group) {
            $total += $group->getTotal();
        }

        return $total;
    }

    /**
     * Gets all calculation items.
     *
     * @return \Traversable|CalculationItem[]
     */
    public function getItems(): \Traversable
    {
        foreach ($this->groups as $group) {
            /** @var CalculationCategory $category */
            foreach ($group->getCategories() as $category) {
                foreach ($category->getItems() as $item) {
                    yield $item;
                }
            }
        }
    }

    /**
     * Gets the items total.
     */
    public function getItemsTotal(): float
    {
        return $this->itemsTotal;
    }

    /**
     * Gets the number of item lines.
     */
    public function getLinesCount(): int
    {
        $count = 0;
        foreach ($this->groups as $group) {
            /** @var CalculationCategory $category */
            foreach ($group->getCategories() as $category) {
                $count += $category->getItems()->count();
            }
        }

        return $count;
    }

    /**
     * Gets the overall margin in percent.
     */
    public function getOverallMargin(): float
    {
        return $this->safeDivide($this->overallTotal, $this->itemsTotal);
    }

    /**
     * Gets the overall margin amount.
     */
    public function getOverallMarginAmount(): float
    {
        if (!empty($this->itemsTotal)) {
            return $this->overallTotal - $this->itemsTotal;
        }

        return 0;
    }

    /**
     * Get overall total.
     */
    public function getOverallTotal(): float
    {
        return $this->overallTotal;
    }

    /**
     * Gets sorted groups.
     *
     * @return CalculationGroup[]
     */
    public function getSortedGroups(): array
    {
        $result = [];
        if (!$this->isEmpty()) {
            foreach ($this->groups as $group) {
                $result[$group->getCode()] = $group;
            }
            \ksort($result, SORT_NATURAL);
        }

        return $result;
    }

    /**
     * Get state.
     */
    public function getState(): ?CalculationState
    {
        return $this->state;
    }

    /**
     * Gets the state code.
     */
    public function getStateCode(): ?string
    {
        return $this->state ? $this->state->getCode() : null;
    }

    /**
     * Gets the state color.
     */
    public function getStateColor(): ?string
    {
        return $this->state ? $this->state->getColor() : null;
    }

    /**
     * Get total net.
     */
    public function getTotalNet(): float
    {
        return $this->getGroupsTotal() * (1 + $this->globalMargin);
    }

    /**
     * Get user margin.
     */
    public function getUserMargin(): float
    {
        return $this->userMargin;
    }

    /**
     * Get user margin amount.
     */
    public function getUserMarginAmount(): float
    {
        return $this->getTotalNet() * $this->userMargin;
    }

    /**
     * Returns a value indicating if one or more items are duplicate.
     *
     * Items are duplicate when two or more item descriptions are equal.
     *
     * @return bool true if duplicates items
     *
     * @see Calculation::getDuplicateItems()
     * @see Calculation::removeDuplicateItems()
     */
    public function hasDuplicateItems(): bool
    {
        if (!$this->isEmpty()) {
            $keys = [];

            /** @var CalculationItem $item */
            foreach ($this->getItems() as $item) {
                $key = $this->getItemKey($item);
                if (\in_array($key, $keys, true)) {
                    return true;
                }
                $keys[] = $key;
            }
        }

        return false;
    }

    /**
     * Returns a value indicating if one or more items are empty.
     *
     * Items are empty if the price or the quantity is equal to zero.
     *
     * @return bool true if empty items
     *
     * @see Calculation::getEmptyItems()
     * @see Calculation::removeEmptyItems()
     */
    public function hasEmptyItems(): bool
    {
        if (!$this->isEmpty()) {
            /** @var CalculationItem $item */
            foreach ($this->getItems() as $item) {
                if ($item->isEmpty()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets editable state.
     */
    public function isEditable(): bool
    {
        return $this->isNew() || empty($this->state) || $this->state->isEditable();
    }

    /**
     * Checks whether the groups is empty (contains no elements).
     *
     * @return bool true if the groups is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return $this->groups->isEmpty();
    }

    /**
     * Returns if this overall margin is below the given minimum margin.
     *
     * To be below, the calculation must have:
     * <ul>
     * <li>One or more items.</li>
     * <li>An overall total different from 0.</li>
     * <li>An overall margin below the given margin.</li>
     * </ul>
     *
     * @param float $margin the minimum margin to be tested
     *
     * @return bool true if below
     */
    public function isMarginBelow(float $margin): bool
    {
        if ($this->isEmpty()) {
            return false;
        } elseif ($this->isFloatZero($this->getOverallTotal())) {
            return false;
        } else {
            return $this->getOverallMargin() < $margin;
        }
    }

    /**
     * Returns if this calculation is sortable.
     *
     * @return bool true if sortable; false otherwise
     */
    public function isSortable(): bool
    {
        if (!$this->isEmpty()) {
            foreach ($this->groups as $group) {
                if ($group->isSortable()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Remove the duplicate items.
     *
     * All empty groups after deletion of the items are also removed.
     * The total of this calculation must be updated after this return call.
     *
     * @return int the number of items removed
     *
     * @see Calculation::getDuplicateItems()
     * @see Calculation::hasDuplicateItems()
     */
    public function removeDuplicateItems(): int
    {
        /** @var CalculationItem[] $items */
        $items = $this->getDuplicateItems();
        if (empty($items)) {
            return 0;
        }

        $keys = [];
        foreach ($items as $item) {
            $key = $this->getItemKey($item);
            if (\in_array($key, $keys, true)) {
                $this->removeItem($item);
            }
            $keys[] = $key;
        }

        return \count($items);
    }

    /**
     * Remove the empty items.
     *
     * All empty groups after deletion of the items are also removed.
     * The total of this calculation must be updated after this return call.
     *
     * @return int the number of items removed
     *
     * @see Calculation::getEmptyItems()
     * @see Calculation::hasEmptyItems()
     */
    public function removeEmptyItems(): int
    {
        /** @var CalculationItem[] $items */
        $items = $this->getEmptyItems();
        if (empty($items)) {
            return 0;
        }

        foreach ($items as $item) {
            $this->removeItem($item);
        }

        return \count($items);
    }

    /**
     * Remove a group.
     *
     * @param CalculationGroup $group the group to remove
     */
    public function removeGroup(CalculationGroup $group): self
    {
        if ($this->groups->removeElement($group)) {
            if ($group->getCalculation() === $this) {
                $group->setCalculation(null);
            }
        }

        return $this;
    }

    /**
     * Set customer.
     *
     * @param string $customer
     */
    public function setCustomer(?string $customer): self
    {
        $this->customer = $this->trim($customer);

        return $this;
    }

    /**
     * Set date.
     */
    public function setDate(\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $this->trim($description);

        return $this;
    }

    /**
     * Set global margin.
     */
    public function setGlobalMargin(float $globalMargin): self
    {
        $this->globalMargin = $this->round($globalMargin);

        return $this;
    }

    /**
     * Sets the key.
     *
     * @param int $id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the items total.
     */
    public function setItemsTotal(float $itemsTotal): self
    {
        $this->itemsTotal = $this->round($itemsTotal);

        return $this;
    }

    /**
     * Set overall total.
     */
    public function setOverallTotal(float $overallTotal): self
    {
        $this->overallTotal = $this->round($overallTotal);

        return $this;
    }

    /**
     * Set state.
     */
    public function setState(?CalculationState $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Set user margin.
     */
    public function setUserMargin(float $userMargin): self
    {
        $this->userMargin = $this->round($userMargin);

        return $this;
    }

    /**
     * Sorts groups and items in alphabetical order.
     *
     * @return bool true if the order has changed
     */
    public function sort(): bool
    {
        if (!$this->isSortable()) {
            return false;
        }

        // sort items
        $changed = false;
        foreach ($this->groups as $group) {
            if ($group->sort()) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->customer,
            $this->description,
            FormatUtils::formatId($this->id),
            FormatUtils::formatDate($this->date),
            $this->getStateCode(),
        ];
    }

    /**
     * Gets the key for the given item.
     *
     * @param CalculationItem $item the item to get key for
     *
     * @return string the key
     */
    private function getItemKey(CalculationItem $item): string
    {
        return \strtolower($item->getDescription());
    }

    /**
     * Remove the given item.
     *
     * If the category and the parent's group ar empty after deletion of the item, the category and the group is also deleted.
     *
     * @param CalculationItem $item the item to remove
     */
    private function removeItem(CalculationItem $item): bool
    {
        $category = $item->getCategory();
        if (null !== $category && $category->contains($item)) {
            $category->removeItem($item);
            if ($category->isEmpty()) {
                $group = $category->getGroup();
                $group->removeCategory($category);
                if ($group->isEmpty()) {
                    $this->removeGroup($group);
                }
            }

            return true;
        }

        return false;
    }
}
