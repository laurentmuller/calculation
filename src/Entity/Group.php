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

use App\Repository\GroupRepository;
use App\Traits\ValidateMarginsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a group of categories and margins.
 */
#[ORM\Table(name: 'sy_Group')]
#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_group_code', columns: ['code'])]
#[UniqueEntity(fields: 'code', message: 'group.unique_code')]
class Group extends AbstractCodeEntity
{
    /** @use ValidateMarginsTrait<GroupMargin> */
    use ValidateMarginsTrait;

    /**
     * The children categories.
     *
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(
        targetEntity: Category::class,
        mappedBy: 'group',
        cascade: ['persist', 'remove'],
        fetch: self::EXTRA_LAZY,
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['code' => \SortDirection::Ascending])]
    public Collection $categories;

    /**
     * The children margins.
     *
     * @var Collection<int, GroupMargin>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: GroupMargin::class,
        mappedBy: 'group',
        cascade: ['persist', 'remove'],
        fetch: self::EXTRA_LAZY,
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['minimum' => \SortDirection::Ascending])]
    public Collection $margins {
        get => $this->margins;
    }

    public function __construct()
    {
        $this->margins = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    #[\Override]
    public function __clone(): void
    {
        parent::__clone();
        $this->margins = $this->margins->map(
            fn (GroupMargin $margin): GroupMargin => (clone $margin)->withGroup($this)
        );
    }

    /**
     * Add a category.
     */
    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->withGroup($this);
        }

        return $this;
    }

    /**
     * Add a margin.
     */
    public function addMargin(GroupMargin $margin): self
    {
        if (!$this->margins->contains($margin)) {
            $this->margins->add($margin);
            $margin->withGroup($this);
        }

        return $this;
    }

    /**
     * Gets the number of categories.
     */
    public function countCategories(): int
    {
        return $this->categories->count();
    }

    /**
     * Gets the number of products and tasks.
     */
    public function countItems(): int
    {
        return $this->reduceCategories(
            static fn (int $carry, Category $category): int => $carry + $category->countItems()
        );
    }

    /**
     * Gets the number of margins.
     */
    public function countMargins(): int
    {
        return $this->margins->count();
    }

    /**
     * Gets the number of products.
     */
    public function countProducts(): int
    {
        return $this->reduceCategories(
            static fn (int $carry, Category $category): int => $carry + $category->countProducts()
        );
    }

    /**
     * Gets the number of tasks.
     */
    public function countTasks(): int
    {
        return $this->reduceCategories(
            static fn (int $carry, Category $category): int => $carry + $category->countTasks()
        );
    }

    /**
     * Finds the group margin for the given amount.
     *
     * @param float $amount the amount to get group margin for
     *
     * @return GroupMargin|null the group margin, if found; null otherwise
     */
    public function findMargin(float $amount): ?GroupMargin
    {
        return $this->margins->findFirst(
            static fn (int $key, GroupMargin $margin): bool => $margin->contains($amount)
        );
    }

    /**
     * Finds the margin in percent for the given amount.
     *
     * @param float $amount the amount to get percent for
     *
     * @return float the percentage of the group margin, if found; 0 otherwise
     *
     * @see Group::findMargin()
     */
    public function findPercent(float $amount): float
    {
        return $this->findMargin($amount)->margin ?? 0.0;
    }

    /**
     * Returns if this group contains no category.
     */
    public function isEmptyCategories(): bool
    {
        return $this->categories->isEmpty();
    }

    /**
     * Returns if this group contains no margin.
     */
    public function isEmptyMargins(): bool
    {
        return $this->margins->isEmpty();
    }

    /**
     * Returns if this group contains no product.
     */
    public function isEmptyProducts(): bool
    {
        if ($this->isEmptyCategories()) {
            return true;
        }

        return $this->categories->forAll(
            static fn (int $key, Category $category): bool => $category->isEmptyProducts()
        );
    }

    /**
     * Returns if this category contains no task.
     */
    public function isEmptyTasks(): bool
    {
        if ($this->isEmptyCategories()) {
            return true;
        }

        return $this->categories->forAll(
            static fn (int $key, Category $category): bool => $category->isEmptyTasks()
        );
    }

    /**
     * Remove a category.
     */
    public function removeCategory(Category $category): self
    {
        if ($this->categories->removeElement($category) && $category->group === $this) {
            $category->withGroup(null);
        }

        return $this;
    }

    /**
     * Remove a margin.
     */
    public function removeMargin(GroupMargin $margin): self
    {
        if ($this->margins->removeElement($margin) && $margin->getParentEntity() === $this) {
            $margin->withGroup(null);
        }

        return $this;
    }

    /**
     * @param \Closure(int, Category): int $func
     */
    private function reduceCategories(\Closure $func): int
    {
        if ($this->categories->isEmpty()) {
            return 0;
        }

        return $this->categories->reduce($func, 0);
    }
}
