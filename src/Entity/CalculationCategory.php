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
use App\Interfaces\ComparableInterface;
use App\Interfaces\ParentTimestampableInterface;
use App\Interfaces\PositionInterface;
use App\Repository\CalculationCategoryRepository;
use App\Traits\CollectionTrait;
use App\Traits\PositionTrait;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation category.
 *
 * @implements ParentTimestampableInterface<Calculation>
 * @implements ComparableInterface<CalculationCategory>
 */
#[ORM\Table(name: 'sy_CalculationCategory')]
#[ORM\Entity(repositoryClass: CalculationCategoryRepository::class)]
class CalculationCategory extends AbstractEntity implements \Countable, ComparableInterface, ParentTimestampableInterface, PositionInterface
{
    use CollectionTrait;
    use PositionTrait;

    /** The total amount. */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $amount = 0.0 {
        set => $this->round($value);
    }

    /** The parent's category. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    public ?Category $category = null;

    /** The code. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH)]
    public ?string $code = null {
        set => StringUtils::trim($value);
    }

    /** The parent's group. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'group_id', nullable: false, onDelete: 'cascade')]
    public ?CalculationGroup $group = null;

    /**
     * The items.
     *
     * @var Collection<int, CalculationItem>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: CalculationItem::class,
        mappedBy: 'category',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    public Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->items = $this->items->map(
            fn (CalculationItem $item): CalculationItem => (clone $item)->withCategory($this)
        );
    }

    /**
     * Add an item.
     */
    public function addItem(CalculationItem $item): self
    {
        if (!$this->contains($item)) {
            $this->items->add($item);
            $item->withCategory($this);
        }

        return $this;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->code, (string) $other->code);
    }

    /**
     * Checks whether the given item is contained within this collection of items.
     *
     * @return bool true if this collection contains the item, false otherwise
     */
    public function contains(CalculationItem $item): bool
    {
        return $this->items->contains($item);
    }

    /**
     * Gets the number of items.
     */
    #[\Override]
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
        return new self()->withCategory($category);
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->code;
    }

    #[\Override]
    public function getParentEntity(): ?Calculation
    {
        return $this->group?->getParentEntity();
    }

    /**
     * Checks whether the category is empty (contains no item).
     *
     * @return bool true if groups are empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Returns if this category is sortable.
     *
     * @return bool true if sortable; false otherwise
     */
    public function isSortable(): bool
    {
        return $this->count() > 1;
    }

    /**
     * Remove an item.
     */
    public function removeItem(CalculationItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->category === $this) {
                $item->withCategory(null);
            }

            return $this->updatePositions();
        }

        return $this;
    }

    /**
     * Sorts items by the alphabetical order of descriptions and update positions.
     *
     * @return bool true if the order has changed
     */
    public function sort(): bool
    {
        // items?
        if (!$this->isSortable()) {
            return false;
        }

        /** @var CalculationItem[] $items */
        $items = $this->getSortedCollection($this->items);

        $position = 0;
        $changed = false;
        foreach ($items as $item) {
            if ($position !== $item->getPosition()) {
                $item->setPosition($position);
                $changed = true;
            }
            ++$position;
        }

        return $changed;
    }

    /**
     * Update the items and the total amount.
     */
    public function update(): self
    {
        // update items
        $amount = 0.0;
        foreach ($this->items as $item) {
            $item->withCategory($this);
            $amount += $item->total;
        }

        return $this->withAmount($amount);
    }

    /**
     * Update this code.
     */
    public function updateCode(): bool
    {
        if ($this->category instanceof Category && $this->code !== $this->category->code) {
            $this->code = $this->category->code;

            return true;
        }

        return false;
    }

    /**
     * Update the position of items.
     */
    public function updatePositions(): self
    {
        $position = 0;
        foreach ($this->items as $item) {
            $item->setPosition($position++);
        }

        return $this;
    }

    /**
     * Set the total amount.
     */
    public function withAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the category.
     *
     * @param Category $category the category to copy values from
     * @param bool     $update   true to update the amount and the margin
     */
    public function withCategory(Category $category, bool $update = false): self
    {
        // copy
        $this->category = $category;
        $this->code = $category->code;

        if ($update) {
            return $this->update();
        }

        return $this;
    }

    /**
     * Set the code.
     */
    public function withCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Set the parent's group.
     */
    public function withGroup(?CalculationGroup $group): self
    {
        $this->group = $group;

        return $this;
    }
}
