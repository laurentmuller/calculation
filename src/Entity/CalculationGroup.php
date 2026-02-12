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
use App\Repository\CalculationGroupRepository;
use App\Traits\CollectionTrait;
use App\Traits\PositionTrait;
use App\Types\FixedFloatType;
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
    private float $amount = 0.0;

    /** The parent's calculation. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'calculation_id', nullable: false, onDelete: 'cascade')]
    private ?Calculation $calculation = null;

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
    #[ORM\OrderBy(['position' => SortModeInterface::SORT_ASC])]
    private Collection $categories;

    /** The code. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH)]
    private ?string $code = null;

    /** The parent's group. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'group_id', nullable: false)]
    private ?Group $group = null;

    /** The margin in percent (%). */
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $margin = 0.0;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->categories = $this->categories->map(
            fn (CalculationCategory $category): CalculationCategory => (clone $category)->setGroup($this)
        );
    }

    /**
     * Add a category.
     */
    public function addCategory(CalculationCategory $category): self
    {
        if (!$this->contains($category)) {
            $this->categories->add($category);
            $category->setGroup($this);
        }

        return $this;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->getCode(), (string) $other->getCode());
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
     *
     * @return int<0, max>
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
        $created = new self();

        return $created->setGroup($group);
    }

    /**
     * Finds or create a calculation category for the given category.
     */
    public function findOrCreateCategory(Category $category): CalculationCategory
    {
        $code = $category->getCode();
        /** @phpstan-var CalculationCategory|null $calculationCategory */
        $calculationCategory = $this->categories->findFirst(
            static fn (int $key, CalculationCategory $category): bool => $code === $category->getCode()
        );
        if ($calculationCategory instanceof CalculationCategory) {
            return $calculationCategory;
        }

        $calculationCategory = CalculationCategory::create($category);
        $this->addCategory($calculationCategory);

        return $calculationCategory;
    }

    /**
     * Get the total amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the calculation categories.
     *
     * @return Collection<int, CalculationCategory>
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

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->getCode();
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
        return $this->amount * ($this->margin - 1.0);
    }

    #[\Override]
    public function getParentEntity(): ?Calculation
    {
        return $this->calculation;
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
            if ($category->getGroup() === $this) {
                $category->setGroup(null);
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
     * Set the parent's calculation.
     */
    public function setCalculation(?Calculation $calculation): self
    {
        $this->calculation = $calculation;

        return $this;
    }

    /**
     * Set code.
     */
    public function setCode(?string $code): self
    {
        $this->code = StringUtils::trim($code);

        return $this;
    }

    /**
     * Set the group.
     *
     * @param Group $group  the group to copy values from
     * @param bool  $update true to update the total amount and the margin
     */
    public function setGroup(Group $group, bool $update = false): self
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
     * Set the margin.
     */
    public function setMargin(float $margin): self
    {
        $this->margin = $this->round($margin);

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

        /** @phpstan-var CalculationCategory[] $categories */
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
            $category->update()->setGroup($this);
            $amount += $category->getAmount();
        }

        // margin
        $margin = $this->group?->findPercent($amount) ?? 0.0;

        return $this->setAmount($amount)
            ->setMargin($margin);
    }

    /**
     * Update this code.
     */
    public function updateCode(): bool
    {
        if ($this->group instanceof Group && $this->code !== $this->group->getCode()) {
            $this->code = $this->group->getCode();

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
}
