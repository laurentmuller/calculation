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
use App\Interfaces\TimestampableInterface;
use App\Repository\CalculationRepository;
use App\Traits\CollectionTrait;
use App\Traits\MathTrait;
use App\Traits\TimestampableTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\DayPointType;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation.
 */
#[ORM\Table(name: 'sy_Calculation')]
#[ORM\Entity(repositoryClass: CalculationRepository::class)]
class Calculation extends AbstractEntity implements TimestampableInterface
{
    use CollectionTrait;
    use MathTrait;
    use TimestampableTrait;

    /** Gets the number of categories. */
    public int $categoriesCount {
        get => $this->reduceGroups(static fn (int $carry, CalculationGroup $group): int => $carry + $group->count(), 0);
    }

    /** The customer name. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    public ?string $customer = null {
        set => StringUtils::trim($value);
    }

    /** The date. */
    #[Assert\NotNull]
    #[ORM\Column(type: DayPointType::NAME)]
    public DatePoint $date;

    /** The description. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    public ?string $description = null {
        set => StringUtils::trim($value);
    }

    /** Get the formatted date. */
    public string $formattedDate {
        get => FormatUtils::formatDate($this->date);
    }

    /** Get the formatted identifier. */
    public string $formattedId {
        get => FormatUtils::formatId((int) $this->id);
    }

    /** The global margin in percent (%). */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $globalMargin = 0.0 {
        set => $this->round($value);
    }

    /** Get the global margin amount. */
    public float $globalMarginAmount {
        get => $this->groupsTotal * ($this->globalMargin - 1.0);
    }

    /**
     * The groups.
     *
     * @var Collection<int, CalculationGroup>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: CalculationGroup::class,
        mappedBy: 'calculation',
        cascade: ['persist', 'remove'],
        fetch: self::EXTRA_LAZY,
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    public Collection $groups;

    /** Gets the total amount for all groups. */
    public float $groupsAmount {
        get => $this->reduceGroups(
            static fn (float $carry, CalculationGroup $group): float => $carry + $group->amount,
            0.0
        );
    }

    /** Gets the number of groups. */
    public int $groupsCount {
        get => $this->groups->count();
    }

    /** Gets the margin of all groups. */
    public float $groupsMargin {
        get => 1.0 + $this->safeDivide($this->groupsMarginAmount, $this->groupsAmount);
    }

    /** Gets the total margin amounts for all groups. */
    public float $groupsMarginAmount {
        get => $this->reduceGroups(
            static fn (float $carry, CalculationGroup $group): float => $carry + $group->marginAmount,
            0.0
        );
    }

    /** Gets the total of all groups. */
    public float $groupsTotal {
        get => $this->reduceGroups(
            static fn (float $carry, CalculationGroup $group): float => $carry + $group->total,
            0.0
        );
    }

    /** Gets the number of item lines. */
    public int $itemsCount {
        get => $this->reduceGroups(
            static fn (int $count, CalculationGroup $group): int => $count + $group->itemsCount,
            0
        );
    }

    /** The total of all items. */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $itemsTotal = 0.0 {
        set => $this->round($value);
    }

    /** Gets the overall margin in percent. */
    public float $overallMargin {
        get => $this->getSafeMargin($this->overallTotal, $this->itemsTotal);
    }

    /** Gets the overall margin amount. */
    public float $overallMarginAmount {
        get {
            if ($this->isFloatZero($this->itemsTotal)) {
                return 0.0;
            }

            return $this->overallTotal - $this->itemsTotal;
        }
    }

    /** The overall total. */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $overallTotal = 0.0 {
        set => $this->round($value);
    }

    /** The state. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'calculations')]
    #[ORM\JoinColumn(name: 'state_id', nullable: false)]
    public ?CalculationState $state = null;

    /** Gets the state code. */
    public ?string $stateCode {
        get => $this->state?->code;
    }

    /** Gets the state color. */
    public ?string $stateColor {
        get => $this->state?->color;
    }

    /** Gets the state identifier. */
    public ?int $stateId {
        get => $this->state?->id;
    }

    /** Get the total net. This is the total of the groups multiplied by the global margin. */
    public float $totalNet {
        get => $this->groupsTotal * $this->globalMargin;
    }

    /** The user margin in percent (%). */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $userMargin = 0.0 {
        set => $this->round($value);
    }

    /** Get the user margin amount. */
    public float $userMarginAmount {
        get => $this->totalNet * $this->userMargin;
    }

    /** Get the user total margin amount. */
    public float $userMarginTotal {
        get => $this->totalNet * (1.0 + $this->userMargin);
    }

    public function __construct()
    {
        $this->date = DateUtils::createDatePoint();
        $this->groups = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->date = DateUtils::createDatePoint();
        $this->createdAt = DateUtils::createDatePoint();
        $this->updatedAt = $this->createdAt;
        $this->groups = $this->groups->map(
            fn (CalculationGroup $group): CalculationGroup => (clone $group)->withCalculation($this)
        );
    }

    /**
     * Add a group.
     */
    public function addGroup(CalculationGroup $group): self
    {
        if (!$this->contains($group)) {
            $this->groups->add($group);
            $group->withCalculation($this);
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
        $category = $product->category;
        $item = CalculationItem::create($product)->withQuantity($quantity);
        $newCategory = $this->findOrCreateCategory($category);
        $newCategory->addItem($item);
        $newCategory->update();

        return $this;
    }

    /**
     * Clone this calculation.
     *
     * @param ?CalculationState $state       the new state
     * @param ?string           $description the new description
     */
    public function clone(?CalculationState $state = null, ?string $description = null): self
    {
        $copy = clone $this;
        if ($state instanceof CalculationState) {
            $copy->withState($state);
        }
        if (null !== $description) {
            return $copy->withDescription($description);
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
     * The calculation group is also created if needed.
     */
    public function findOrCreateCategory(Category $category): CalculationCategory
    {
        /** @var Group $categoryGroup */
        $categoryGroup = $category->group;
        $calculationGroup = $this->findOrCreateGroup($categoryGroup);

        return $calculationGroup->findOrCreateCategory($category);
    }

    /**
     * Finds or create a calculation group for the given group.
     */
    public function findOrCreateGroup(Group $group): CalculationGroup
    {
        $code = $group->code;
        /** @var CalculationGroup|null $calculationGroup */
        $calculationGroup = $this->groups->findFirst(
            static fn (int $key, CalculationGroup $group): bool => $code === $group->code
        );
        if ($calculationGroup instanceof CalculationGroup) {
            return $calculationGroup;
        }

        $calculationGroup = CalculationGroup::create($group);
        $this->addGroup($calculationGroup);

        return $calculationGroup;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return $this->formattedId;
    }

    /**
     * Gets the duplicate items.
     *
     * Items are duplicate when two or more item descriptions are equal, ignoring case considerations.
     *
     * @return CalculationItem[] an array, maybe empty of duplicate items
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
        return \array_reduce(
            $array,
            static function (array $carry, array $items): array {
                if (\count($items) > 1) {
                    return \array_merge($carry, \array_values($items));
                }

                return $carry;
            },
            []
        );
    }

    /**
     * Gets the empty items.
     *
     * Items are empty if the price or the quantity is equal to 0.
     *
     * @return CalculationItem[] an array, maybe empty of empty items
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
     * Gets all calculation items.
     *
     * @return \Generator<CalculationItem>
     */
    public function getItems(): \Generator
    {
        foreach ($this->groups as $group) {
            foreach ($group->categories as $category) {
                foreach ($category->items as $item) {
                    yield $item;
                }
            }
        }
    }

    /**
     * Gets sorted groups.
     *
     * @return CalculationGroup[]
     */
    public function getSortedGroups(): array
    {
        return $this->getSortedCollection($this->groups);
    }

    /**
     * Returns a value indicating if one or more items are duplicate.
     *
     * Items are duplicate when two or more item descriptions are equal.
     *
     * @return bool true if duplicates items
     *
     * @phpstan-assert-if-true non-empty-array<CalculationItem> $this->getDuplicateItems()
     */
    public function hasDuplicateItems(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        $keys = [];
        foreach ($this->getItems() as $item) {
            $key = $this->getItemKey($item);
            if (\in_array($key, $keys, true)) {
                return true;
            }
            $keys[] = $key;
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
     * @phpstan-assert-if-true non-empty-array<CalculationItem> $this->getEmptyItems()
     */
    public function hasEmptyItems(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        foreach ($this->getItems() as $item) {
            if ($item->isEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets editable state.
     */
    public function isEditable(): bool
    {
        return $this->isNew() || ($this->state->editable ?? false);
    }

    /**
     * Checks whether the groups are empty (contains no elements).
     *
     * @return bool true if groups are empty, false otherwise
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
        if ($this->isEmpty() || $this->isFloatZero($this->overallTotal)) {
            return false;
        }

        return $this->isBelow($margin, $this->overallMargin);
    }

    /**
     * Returns if this calculation is sortable.
     *
     * @return bool true if sortable; false otherwise
     */
    public function isSortable(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        return $this->groups->exists(
            static fn (int $key, CalculationGroup $group): bool => $group->isSortable()
        );
    }

    /**
     * Remove the duplicate items.
     *
     * All empty categories and groups after deletion of the items are also removed.
     * The total of these calculations must be updated after this return call.
     *
     * @return int the number of items removed
     */
    public function removeDuplicateItems(): int
    {
        $items = $this->getDuplicateItems();
        if ([] === $items) {
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
     * All empty categories and groups after deletion of the items are also removed.
     * The total of these calculations must be updated after this return call.
     *
     * @return int the number of items removed
     */
    public function removeEmptyItems(): int
    {
        $items = $this->getEmptyItems();
        if ([] === $items) {
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
            if ($group->getParentEntity() === $this) {
                $group->withCalculation(null);
            }

            return $this->updatePositions();
        }

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
     * @return int the number of updates
     */
    public function updateCodes(): int
    {
        $total = 0;
        foreach ($this->groups as $group) {
            if ($group->updateCode()) {
                ++$total;
            }
            foreach ($group->categories as $category) {
                if ($category->updateCode()) {
                    ++$total;
                }
            }
        }

        return $total;
    }

    /**
     * Update the position of groups, categories, and items.
     */
    public function updatePositions(): self
    {
        $position = 0;
        foreach ($this->groups as $group) {
            $group->setPosition($position++)
                ->updatePositions();
        }

        return $this;
    }

    /**
     * Set customer.
     */
    public function withCustomer(?string $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Set date.
     */
    public function withDate(DatePoint $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Set description.
     */
    public function withDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the global margin.
     */
    public function withGlobalMargin(float $globalMargin): self
    {
        $this->globalMargin = $globalMargin;

        return $this;
    }

    /**
     * Sets the item's total.
     */
    public function withItemsTotal(float $itemsTotal): self
    {
        $this->itemsTotal = $itemsTotal;

        return $this;
    }

    /**
     * Set overall total.
     */
    public function withOverallTotal(float $overallTotal): self
    {
        $this->overallTotal = $overallTotal;

        return $this;
    }

    /**
     * Set state.
     */
    public function withState(?CalculationState $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Set user margin.
     */
    public function withUserMargin(float $userMargin): self
    {
        $this->userMargin = $userMargin;

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
        return \strtolower((string) $item->description);
    }

    /**
     * @template TValue
     *
     * @param \Closure(TValue, CalculationGroup): TValue $func
     * @param TValue                                     $initial
     *
     * @return TValue
     */
    private function reduceGroups(\Closure $func, mixed $initial): mixed
    {
        if ($this->groups->isEmpty()) {
            return $initial;
        }

        return $this->groups->reduce($func, $initial);
    }

    /**
     * Remove the given item.
     *
     * The parent's category is removed, if empty after item deletion.
     * The parent's group is removed, if empty after category deletion.
     */
    private function removeItem(CalculationItem $item): bool
    {
        $category = $item->category;
        if (!$category instanceof CalculationCategory || !$category->contains($item)) {
            return false;
        }

        $category->removeItem($item);
        if (!$category->isEmpty()) {
            return true;
        }

        $group = $category->group;
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
