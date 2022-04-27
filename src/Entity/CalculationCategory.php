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

use App\Interfaces\ParentCalculationInterface;
use App\Repository\CalculationCategoryRepository;
use App\Traits\PositionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a category of calculation group.
 */
#[ORM\Entity(repositoryClass: CalculationCategoryRepository::class)]
#[ORM\Table(name: 'sy_CalculationCategory')]
class CalculationCategory extends AbstractEntity implements \Countable, ParentCalculationInterface
{
    use PositionTrait;

    /**
     * The total amount.
     */
    #[ORM\Column(type: 'float', scale: 2, options: ['default' => 0])]
    protected float $amount = 0.0;

    /**
     * The parent's category.
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    protected ?Category $category = null;

    /**
     * The code.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    #[ORM\Column(type: 'string', length: 30)]
    protected ?string $code = null;

    /**
     * The parent's group.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: CalculationGroup::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'group_id', nullable: false, onDelete: 'cascade')]
    protected ?CalculationGroup $group = null;

    /**
     * The items.
     *
     * @var Collection<int, CalculationItem>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CalculationItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Criteria::ASC])]
    protected Collection $items;

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
        $this->items = $this->items->map(fn (CalculationItem $item) => (clone $item)->setCategory($this));
    }

    /**
     * Add an item.
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
     * {@inheritDoc}
     */
    public function getCalculation(): ?Calculation
    {
        return $this->group?->getCalculation();
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

    /**
     * {@inheritdoc}
     */
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

        // sort
        $items = $this->items->toArray();
        \uasort($items, static fn (CalculationItem $a, CalculationItem $b): int => \strcasecmp((string) $a->getDescription(), (string) $b->getDescription()));

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
        $amount = 0;
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
        if (null !== $this->category && $this->code !== $this->category->getCode()) {
            $this->code = $this->category->getCode();

            return true;
        }

        return false;
    }

    /**
     * Update position of items.
     */
    public function updatePositions(): self
    {
        $position = 0;

        foreach ($this->items as $item) {
            if ($item->getPosition() !== $position) {
                $item->setPosition($position);
            }
            ++$position;
        }

        return $this;
    }
}
