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
 * @author Laurent Muller
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
     */
    protected ?string $customer = null;

    /**
     * The date.
     *
     * @ORM\Column(type="date")
     * @Assert\NotNull
     */
    protected \DateTimeInterface $date;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    protected ?string $description = null;

    /**
     * The global margin in percent (%).
     *
     * @ORM\Column(name="globalMargin", type="float", scale=2, options={"default" = 0})
     */
    protected float $globalMargin = 0.0;

    /**
     * The groups.
     *
     * @ORM\OneToMany(targetEntity=CalculationGroup::class, mappedBy="calculation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @Assert\Valid
     *
     * @var CalculationGroup[]|Collection
     * @psalm-var Collection<int, CalculationGroup>
     */
    protected Collection $groups;

    /**
     * The total of all items.
     *
     * @ORM\Column(name="itemsTotal", type="float", scale=2, options={"default" = 0})
     */
    protected float $itemsTotal = 0.0;

    /**
     * The overall total.
     *
     * @ORM\Column(name="overallTotal", type="float", scale=2, options={"default" = 0})
     */
    protected float $overallTotal = 0.0;

    /**
     * The state.
     *
     * @ORM\ManyToOne(targetEntity=CalculationState::class, inversedBy="calculations")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    protected ?CalculationState $state = null;

    /**
     * The user margin in percent (%).
     *
     * @ORM\Column(name="userMargin", type="float", scale=2, options={"default" = 0})
     */
    protected float $userMargin = 0.0;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->date = new \DateTime();
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

        // clone groupes
        $this->groups = $this->groups->map(fn (CalculationGroup $group) => (clone $group)->setCalculation($this));
    }

    /**
     * Add a group.
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
        /** @var Category $category */
        $category = $product->getCategory();
        $item = CalculationItem::create($product)->setQuantity($quantity);
        $newCategory = $this->findCategory($category);
        $newCategory->addItem($item);
        $newCategory->update();

        return $this;
    }

    /**
     * Clone this calculation.
     *
     * @param CalculationState|null $state       the new state
     * @param string|null           $description the new description
     */
    public function clone(?CalculationState $state = null, ?string $description = null): self
    {
        $copy = clone $this;
        if (null !== $state) {
            $copy->setState($state);
        }
        if (null !== $description) {
            return $copy->setDescription($description);
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
     * Finds or create a calculation category for the given category.
     *
     * @param Category $category the category to find
     */
    public function findCategory(Category $category): CalculationCategory
    {
        // find group
        /** @var Group $categoryGroup */
        $categoryGroup = $category->getGroup();
        $group = $this->findGroup($categoryGroup);

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
     * Finds or create a calculation group for the given group.
     *
     * @param Group $group the group to find
     */
    public function findGroup(Group $group): CalculationGroup
    {
        // find group
        $code = $group->getCode();
        foreach ($this->groups as $current) {
            if ($code === $current->getCode()) {
                return $current;
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
            $count += $group->count();
        }

        return $count;
    }

    /**
     * Get customer.
     */
    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    /**
     * Get date.
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Get description.
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
        return $this->getFormattedId();
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
        /** @var array<string, CalculationItem[]> $array */
        $array = [];
        foreach ($this->getItems() as $item) {
            $key = $this->getItemKey($item);
            $array[$key][] = $item;
        }

        // merge duplicated items
        /** @var CalculationItem[] $result */
        $result = \array_reduce($array, function (array $current, array $items): array {
            if (\count($items) > 1) {
                return \array_merge($current, \array_values($items));
            }

            return $current;
        }, []);

        return $result;
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

        foreach ($this->getItems() as $item) {
            if ($item->isEmpty()) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Get the formatted date.
     */
    public function getFormattedDate(): string
    {
        return (string) FormatUtils::formatDate($this->date);
    }

    /**
     * Get the formatted identifier.
     */
    public function getFormattedId(): string
    {
        return FormatUtils::formatId((int) $this->id);
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
        return $this->getGroupsTotal() * ($this->globalMargin - 1);
    }

    /**
     * Get groups.
     *
     * @return Collection<int, CalculationGroup>
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
     * @return \Generator<CalculationItem>
     */
    public function getItems(): \Generator
    {
        foreach ($this->groups as $group) {
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
            foreach ($group->getCategories() as $category) {
                $count += $category->count();
            }
        }

        return $count;
    }

    /**
     * Gets the overall margin in percent.
     */
    public function getOverallMargin(): float
    {
        $value = $this->safeDivide($this->overallTotal, $this->itemsTotal);

        return \floor($value * 100) / 100;
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
                $result[(string) $group->getCode()] = $group;
            }
            \ksort($result, \SORT_NATURAL);
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
        return $this->state?->getCode();
    }

    /**
     * Gets the state color.
     */
    public function getStateColor(): ?string
    {
        return $this->state?->getColor();
    }

    /**
     * Get total net. This is total of the groups multiplied by the global margin.
     */
    public function getTotalNet(): float
    {
        return $this->getGroupsTotal() * $this->globalMargin;
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
     * Get user total margin amount.
     */
    public function getUserMarginTotal(): float
    {
        return $this->getTotalNet() * (1 + $this->userMargin);
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
     * Checks whether the groups are empty (contains no elements).
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
        if ($this->isEmpty() || $this->isFloatZero($this->getOverallTotal())) {
            return false;
        }

        return $this->getOverallMargin() < $margin;
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
     */
    public function removeGroup(CalculationGroup $group): self
    {
        if ($this->groups->removeElement($group)) {
            if ($group->getCalculation() === $this) {
                $group->setCalculation(null);
            }

            return $this->updatePositions();
        }

        return $this;
    }

    /**
     * Set customer.
     */
    public function setCustomer(?string $customer): self
    {
        $this->customer = $this->trim($customer);

        return $this;
    }

    /**
     * Set date.
     */
    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Set description.
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
     * Sets the identifier.
     */
    public function setId(int $id): self
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
     * Sorts groups, categories and items in alphabetical order.
     *
     * @return bool true if the order has changed
     */
    public function sort(): bool
    {
        if (!$this->isSortable()) {
            return false;
        }

        $changed = false;

        foreach ($this->groups as $group) {
            if ($group->sort()) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Update the group and category codes.
     *
     * @return int the number of update
     */
    public function updateCodes(): int
    {
        $total = 0;

        foreach ($this->groups as $group) {
            if ($group->updateCode()) {
                ++$total;
            }
            foreach ($group->getCategories()  as $category) {
                if ($category->updateCode()) {
                    ++$total;
                }
            }
        }

        return $total;
    }

    /**
     * Update position of groups, categories and items.
     */
    public function updatePositions(): self
    {
        $position = 0;

        foreach ($this->groups as $group) {
            if ($group->getPosition() !== $position) {
                $group->setPosition($position);
            }
            $group->updatePositions();
            ++$position;
        }

        return $this;
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
        return \strtolower((string) $item->getDescription());
    }

    /**
     * Remove the given item.
     *
     * If the category and the parent's group are empty after deletion of the item, the category and the group are also deleted.
     *
     * @param CalculationItem $item the item to remove
     */
    private function removeItem(CalculationItem $item): bool
    {
        $category = $item->getCategory();
        if (!$category instanceof CalculationCategory || !$category->contains($item)) {
            return false;
        }

        $category->removeItem($item);
        if (!$category->isEmpty()) {
            return true;
        }

        $group = $category->getGroup();
        if (!$group instanceof CalculationGroup) {
            return true;
        }

        $group->removeCategory($category);
        if ($group->isEmpty()) {
            $this->removeGroup($group);
        }

        return true;
    }
}
