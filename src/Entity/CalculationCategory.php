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

use App\Interfaces\ComparableInterface;
use App\Interfaces\ParentTimestampableInterface;
use App\Interfaces\PositionInterface;
use App\Interfaces\SortModeInterface;
use App\Repository\CalculationCategoryRepository;
use App\Traits\CollectionTrait;
use App\Traits\PositionTrait;
use App\Types\FixedFloatType;
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

    /**
     * The total amount.
     */
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $amount = 0.0;

    /**
     * The parent's category.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    private ?Category $category = null;

    /**
     * The code.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH)]
    private ?string $code = null;

    /**
     * The parent's group.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'group_id', nullable: false, onDelete: 'cascade')]
    private ?CalculationGroup $group = null;

    /**
     * The items.
     *
     * @var Collection<int, CalculationItem>
     *
     * @phpstan-var ArrayCollection<int, CalculationItem>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: CalculationItem::class,
        mappedBy: 'category',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => SortModeInterface::SORT_ASC])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->items = $this->items->map(
            fn (CalculationItem $item): CalculationItem => (clone $item)->setCategory($this)
        );
    }

    /**
     * Add an item.
     */
    public function addItem(CalculationItem $item): self
    {
        if (!$this->contains($item)) {
            $this->items->add($item);
            $item->setCategory($this);
        }

        return $this;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->getCode(), (string) $other->getCode());
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
     *
     * @return int<0, max>
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
     * Get the category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Get code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->getCode();
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
     * @return Collection<int, CalculationItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
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
            if ($item->getCategory() === $this) {
                $item->setCategory(null);
            }

            return $this->updatePositions();
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
     * @param Category $category the category to copy values from
     * @param bool     $update   true to update the amount and the margin
     */
    public function setCategory(Category $category, bool $update = false): self
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
        $this->code = StringUtils::trim($code);

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

        /** @phpstan-var CalculationItem[] $items */
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
            $item->setCategory($this);
            $amount += $item->getTotal();
        }

        return $this->setAmount($amount);
    }

    /**
     * Update this code.
     */
    public function updateCode(): bool
    {
        if ($this->category instanceof Category && $this->code !== $this->category->getCode()) {
            $this->code = $this->category->getCode();

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
}
