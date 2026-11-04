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
use App\Repository\CalculationGroupRepository;
use App\Traits\CollectionTrait;
use App\Traits\PositionTrait;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation group.
 *
 * @implements ParentTimestampableInterface<Calculation>
 * @implements ComparableInterface<CalculationGroup>
 */
#[ORM\Table(name: 'sy_CalculationGroup')]
#[ORM\Entity(repositoryClass: CalculationGroupRepository::class)]
class CalculationGroup extends AbstractEntity implements \Countable, ComparableInterface, ParentTimestampableInterface, PositionInterface
{
    use CollectionTrait;
    use PositionTrait;

    /** The total amount. */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $amount = 0.0 {
        set => $this->round($value);
    }

    /** The parent's calculation. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'calculation_id', nullable: false, onDelete: 'cascade')]
    public ?Calculation $calculation = null;

    /**
     * The categories.
     *
     * @var Collection<int, CalculationCategory>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: CalculationCategory::class,
        mappedBy: 'group',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    public Collection $categories;

    /** The code. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH)]
    public ?string $code = null {
        set => StringUtils::trim($value);
    }

    /** The parent's group. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'group_id', nullable: false)]
    public ?Group $group = null;

    /** Gets the number of item lines. */
    public int $itemsCount {
        get => $this->categories->reduce(
            static fn (int $count, CalculationCategory $category): int => $count + $category->count(),
            0
        );
    }

    /** The margin in percent (%). */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $margin = 0.0 {
        set => $this->round($value);
    }

    /** Gets the margin amount. */
    public float $marginAmount {
        get => $this->amount * ($this->margin - 1.0);
    }

    /**
     * Gets the total.
     *
     * This is the sum of the amount and the margin amount.
     */
    public float $total {
        get => $this->amount * $this->margin;
    }

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->categories = $this->categories->map(
            fn (CalculationCategory $category): CalculationCategory => (clone $category)->withGroup($this)
        );
    }

    /**
     * Add a category.
     */
    public function addCategory(CalculationCategory $category): self
    {
        if (!$this->contains($category)) {
            $this->categories->add($category);
            $category->withGroup($this);
        }

        return $this;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->code, (string) $other->code);
    }

    /**
     * Checks whether the given category is contained within this collection of categories.
     *
     * @return bool true if this collection contains the category, false otherwise
     */
    public function contains(CalculationCategory $category): bool
    {
        return $this->categories->contains($category);
    }

    /**
     * Gets the number of categories.
     */
    #[\Override]
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
        return new self()->withGroup($group);
    }

    /**
     * Finds or create a calculation category for the given category.
     */
    public function findOrCreateCategory(Category $category): CalculationCategory
    {
        $code = $category->code;
        /** @var CalculationCategory|null $calculationCategory */
        $calculationCategory = $this->categories->findFirst(
            static fn (int $key, CalculationCategory $category): bool => $code === $category->code
        );
        if ($calculationCategory instanceof CalculationCategory) {
            return $calculationCategory;
        }

        $calculationCategory = CalculationCategory::create($category);
        $this->addCategory($calculationCategory);

        return $calculationCategory;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->code;
    }

    #[\Override]
    public function getParentEntity(): ?Calculation
    {
        return $this->calculation;
    }

    /**
     * Checks whether the categories are empty (contains no category).
     *
     * @return bool true if the groups are empty, false otherwise
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
        if ($this->isEmpty()) {
            return false;
        }

        return $this->categories->exists(
            static fn (int $key, CalculationCategory $category): bool => $category->isSortable()
        );
    }

    /**
     * Remove a category.
     */
    public function removeCategory(CalculationCategory $category): self
    {
        if ($this->categories->removeElement($category)) {
            if ($category->group === $this) {
                $category->withGroup(null);
            }

            return $this->updatePositions();
        }

        return $this;
    }

    /**
     * Sorts categories and items in alphabetical order and update positions.
     *
     * @return bool true if the order has changed
     */
    public function sort(): bool
    {
        // categories?
        if (!$this->isSortable()) {
            return false;
        }

        /** @var CalculationCategory[] $categories */
        $categories = $this->getSortedCollection($this->categories);

        $position = 0;
        $changed = false;
        foreach ($categories as $category) {
            if ($position !== $category->getPosition()) {
                $category->setPosition($position);
                $changed = true;
            }
            if ($category->sort()) {
                $changed = true;
            }
            ++$position;
        }

        return $changed;
    }

    /**
     * Update the categories and the total amount.
     */
    public function update(): self
    {
        // update categories
        $amount = 0.0;
        foreach ($this->categories as $category) {
            $category->update()->withGroup($this);
            $amount += $category->amount;
        }

        // margin
        $margin = $this->group?->findPercent($amount) ?? 0.0;

        return $this->withAmount($amount)
            ->withMargin($margin);
    }

    /**
     * Update this code.
     */
    public function updateCode(): bool
    {
        if ($this->group instanceof Group && $this->code !== $this->group->code) {
            $this->code = $this->group->code;

            return true;
        }

        return false;
    }

    /**
     * Update the position of categories and items.
     */
    public function updatePositions(): self
    {
        $position = 0;
        foreach ($this->categories as $category) {
            $category->setPosition($position++)
                ->updatePositions();
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
     * Set the parent's calculation.
     */
    public function withCalculation(?Calculation $calculation): self
    {
        $this->calculation = $calculation;

        return $this;
    }

    /**
     * Set code.
     */
    public function withCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Set the group.
     *
     * @param Group $group  the group to copy values from
     * @param bool  $update true to update the total amount and the margin
     */
    public function withGroup(Group $group, bool $update = false): self
    {
        // copy
        $this->group = $group;
        $this->code = $group->code;

        if ($update) {
            return $this->update();
        }

        return $this;
    }

    /**
     * Set the margin.
     */
    public function withMargin(float $margin): self
    {
        $this->margin = $margin;

        return $this;
    }
}
