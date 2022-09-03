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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a group of categories.
 */
#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'sy_Group')]
#[ORM\UniqueConstraint(name: 'unique_group_code', columns: ['code'])]
#[UniqueEntity(fields: 'code', message: 'group.unique_code')]
class Group extends AbstractEntity
{
    use ValidateMarginsTrait;

    /**
     * The children categories.
     *
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Category::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['code' => Criteria::ASC])]
    private Collection $categories;

    /**
     * The unique code.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    #[ORM\Column(length: 30, unique: true)]
    private ?string $code = null;

    /**
     * The description.
     */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    /**
     * The children margins.
     *
     * @var Collection<int, GroupMargin>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMargin::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['minimum' => Criteria::ASC])]
    private Collection $margins;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->margins = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    /**
     * Add a category.
     */
    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
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
            $this->margins[] = $margin;
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
        if ($code) {
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
        return $this->reduceCategories(fn (int $carry, Category $category): int => $carry + $category->countItems());
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
        return $this->reduceCategories(fn (int $carry, Category $category): int => $carry + $category->countProducts());
    }

    /**
     * Gets the number of tasks.
     */
    public function countTasks(): int
    {
        return $this->reduceCategories(fn (int $carry, Category $category): int => $carry + $category->countTasks());
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
        foreach ($this->margins as $margin) {
            if ($margin->contains($amount)) {
                return $margin;
            }
        }

        return null;
    }

    /**
     * Finds the margin in percent for the given amount.
     *
     * @param float $amount the amount to get percent for
     *
     * @return float the percent of the group margin, if found; 0 otherwise
     *
     * @see Group::findMargin()
     */
    public function findPercent(float $amount): float
    {
        $margin = $this->findMargin($amount);

        return null !== $margin ? $margin->getMargin() : 0;
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
     * Get code.
     */
    public function getCode(): ?string
    {
        return $this->code;
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
        return (string) $this->getCode();
    }

    /**
     * Get margins.
     *
     * @return Collection<int, GroupMargin>
     */
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
        if ($this->margins->removeElement($margin) && $margin->getGroup() === $this) {
            $margin->setGroup(null);
        }

        return $this;
    }

    /**
     * Set code.
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Set description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Iteratively reduce these categories to a single value using the callback function.
     *
     * @param callable(int, Category): int $callback
     */
    private function reduceCategories(callable $callback): int
    {
        return \array_reduce($this->categories->toArray(), $callback, 0);
    }
}
