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

use App\Interfaces\SortModeInterface;
use App\Repository\GroupRepository;
use App\Traits\ValidateMarginsTrait;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a group of categories.
 */
#[ORM\Table(name: 'sy_Group')]
#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_group_code', columns: ['code'])]
#[UniqueEntity(fields: 'code', message: 'group.unique_code')]
class Group extends AbstractCodeEntity
{
    /**
     * @use ValidateMarginsTrait<int, GroupMargin>
     */
    use ValidateMarginsTrait;

    /**
     * The children categories.
     *
     * @var Collection<int, Category>
     *
     * @phpstan-var ArrayCollection<int, Category>
     */
    #[ORM\OneToMany(
        targetEntity: Category::class,
        mappedBy: 'group',
        cascade: ['persist', 'remove'],
        fetch: self::EXTRA_LAZY,
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['code' => SortModeInterface::SORT_ASC])]
    private Collection $categories;

    /**
     * The children margins.
     *
     * @var Collection<int, GroupMargin>
     *
     * @phpstan-var ArrayCollection<int, GroupMargin>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(
        targetEntity: GroupMargin::class,
        mappedBy: 'group',
        cascade: ['persist', 'remove'],
        fetch: self::EXTRA_LAZY,
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['minimum' => SortModeInterface::SORT_ASC])]
    private Collection $margins;

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
            fn (GroupMargin $margin): GroupMargin => (clone $margin)->setGroup($this)
        );
    }

    /**
     * Add a category.
     */
    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setGroup($this);
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
            $margin->setGroup($this);
        }

        return $this;
    }

    /**
     * Clone this group.
     *
     * @param ?string $code the new code
     */
    public function clone(?string $code = null): self
    {
        $copy = clone $this;
        if (StringUtils::isString($code)) {
            $copy->setCode($code);
        }

        return $copy;
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
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function findMargin(float $amount): ?GroupMargin
    {
        /** @phpstan-var GroupMargin|null */
        return $this->margins->findFirst(
            fn (int $key, GroupMargin $margin): bool => $margin->contains($amount)
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
        return $this->findMargin($amount)?->getMargin() ?? 0.0;
    }

    /**
     * Get categories.
     *
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * Get margins.
     *
     * @return Collection<int, GroupMargin>
     */
    #[\Override]
    public function getMargins(): Collection
    {
        return $this->margins;
    }

    /**
     * Returns if this group contains one or more categories.
     */
    public function hasCategories(): bool
    {
        return !$this->categories->isEmpty();
    }

    /**
     * Returns if this group contains one or more margins.
     */
    public function hasMargins(): bool
    {
        return !$this->margins->isEmpty();
    }

    /**
     * Returns if this group contains one or more products.
     */
    public function hasProducts(): bool
    {
        if (!$this->hasCategories()) {
            return false;
        }

        return $this->categories->exists(
            static fn (int $key, Category $category): bool => $category->hasProducts()
        );
    }

    /**
     * Returns if this category contains one or more tasks.
     */
    public function hasTasks(): bool
    {
        if (!$this->hasCategories()) {
            return false;
        }

        return $this->categories->exists(
            static fn (int $key, Category $category): bool => $category->hasTasks()
        );
    }

    /**
     * Remove a category.
     */
    public function removeCategory(Category $category): self
    {
        if ($this->categories->removeElement($category) && $category->getGroup() === $this) {
            $category->setGroup(null);
        }

        return $this;
    }

    /**
     * Remove a margin.
     */
    public function removeMargin(GroupMargin $margin): self
    {
        if ($this->margins->removeElement($margin) && $margin->getParentEntity() === $this) {
            $margin->setGroup(null);
        }

        return $this;
    }

    /**
     * @param \Closure(int, Category): int $func
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private function reduceCategories(\Closure $func): int
    {
        if ($this->categories->isEmpty()) {
            return 0;
        }

        /** @phpstan-var int */
        return $this->categories->reduce($func, 0);
    }
}
