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

use App\Interfaces\ParentTimestampableInterface;
use App\Interfaces\TimestampableInterface;
use App\Repository\CalculationGroupRepository;
use App\Traits\PositionTrait;
use App\Types\FixedFloatType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a group of a calculation.
 */
#[ORM\Entity(repositoryClass: CalculationGroupRepository::class)]
#[ORM\Table(name: 'sy_CalculationGroup')]
class CalculationGroup extends AbstractEntity implements \Countable, ParentTimestampableInterface
{
    use PositionTrait;

    /**
     * The total amount.
     */
    #[ORM\Column(type: FixedFloatType::NAME)]
    protected float $amount = 0.0;

    /**
     * The parent's calculation.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'calculation_id', nullable: false, onDelete: 'cascade')]
    protected ?Calculation $calculation = null;

    /**
     * The categories.
     *
     * @var Collection<int, CalculationCategory>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: CalculationCategory::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Criteria::ASC])]
    protected Collection $categories;

    /**
     * The code.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH)]
    protected ?string $code = null;

    /**
     * The parent's group.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'group_id', nullable: false)]
    protected ?Group $group = null;

    /**
     * The margin in percent (%).
     */
    #[ORM\Column(type: FixedFloatType::NAME)]
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
        $this->categories = $this->categories->map(fn (CalculationCategory $category) => (clone $category)->setGroup($this));
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
     * {@inheritDoc}
     */
    public function getCalculation(): ?Calculation
    {
        return $this->calculation;
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

    /**
     * {@inheritdoc}
     */
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
        return $this->amount * ($this->margin - 1);
    }

    /**
     * {@inheritDoc}
     */
    public function getParentTimestampable(): ?TimestampableInterface
    {
        return $this->getCalculation();
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
     * Checks whether the categories are empty (contains no categories).
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
        $this->code = $this->trim($code);

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
     * Set margin.
     */
    public function setMargin(float $margin): self
    {
        $this->margin = $this->round($margin);

        return $this;
    }

    /**
     * Sorts categories and items in alphabetical order.
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
        $categories = $this->categories->toArray();
        \uasort($categories, static fn (CalculationCategory $a, CalculationCategory $b): int => \strcasecmp((string) $a->getCode(), (string) $b->getCode()));

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
        $amount = 0;
        foreach ($this->categories as $category) {
            $category->update()->setGroup($this);
            $amount += $category->getAmount();
        }

        // margin
        $margin = null !== $this->group ? $this->group->findPercent($amount) : 0.0;

        return $this->setAmount($amount)->setMargin($margin);
    }

    /**
     * Update this code.
     */
    public function updateCode(): bool
    {
        if (null !== $this->group && $this->code !== $this->group->getCode()) {
            $this->code = $this->group->getCode();

            return true;
        }

        return false;
    }

    /**
     * Update position of categories and items.
     */
    public function updatePositions(): self
    {
        $position = 0;

        foreach ($this->categories as $category) {
            if ($category->getPosition() !== $position) {
                $category->setPosition($position);
            }
            $category->updatePositions();
            ++$position;
        }

        return $this;
    }
}
