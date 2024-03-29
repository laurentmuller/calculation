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
use App\Interfaces\TimestampableInterface;
use App\Repository\GroupRepository;
use App\Traits\TimestampableTrait;
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
class Group extends AbstractEntity implements TimestampableInterface
{
    use TimestampableTrait;
    use ValidateMarginsTrait;

    /**
     * The children categories.
     *
     * @var ArrayCollection<int, Category>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Category::class, cascade: ['persist', 'remove'], fetch: self::EXTRA_LAZY, orphanRemoval: true)]
    #[ORM\OrderBy(['code' => SortModeInterface::SORT_ASC])]
    private Collection $categories;

    /**
     * The unique code.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH, unique: true)]
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
     * @var ArrayCollection<int, GroupMargin>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMargin::class, cascade: ['persist', 'remove'], fetch: self::EXTRA_LAZY, orphanRemoval: true)]
    #[ORM\OrderBy(['minimum' => SortModeInterface::SORT_ASC])]
    private Collection $margins;

    public function __construct()
    {
        $this->margins = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function __clone()
    {
        parent::__clone();
        $this->margins = $this->margins->map(fn (GroupMargin $margin): GroupMargin => (clone $margin)->setGroup($this));
    }

    /**
     * Add a category.
     *
     * @psalm-api
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
        if (!$this->hasCategories()) {
            return 0;
        }

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
        if (!$this->hasCategories()) {
            return 0;
        }

        return $this->reduceCategories(fn (int $carry, Category $category): int => $carry + $category->countProducts());
    }

    /**
     * Gets the number of tasks.
     */
    public function countTasks(): int
    {
        if (!$this->hasCategories()) {
            return 0;
        }

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

        return $margin instanceof GroupMargin ? $margin->getMargin() : 0;
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
        return 0 !== $this->categories->count();
    }

    /**
     * Returns if this group contains one or more margins.
     */
    public function hasMargins(): bool
    {
        return 0 !== $this->margins->count();
    }

    /**
     * Returns if this group contains one or more products.
     *
     * @psalm-api
     */
    public function hasProducts(): bool
    {
        if (!$this->hasCategories()) {
            return false;
        }
        foreach ($this->categories as $category) {
            if ($category->hasProducts()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns if this category contains one or more tasks.
     *
     * @psalm-api
     */
    public function hasTasks(): bool
    {
        if (!$this->hasCategories()) {
            return false;
        }
        foreach ($this->categories as $category) {
            if ($category->hasTasks()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a category.
     *
     * @psalm-api
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
     *
     * @psalm-api
     */
    public function removeMargin(GroupMargin $margin): self
    {
        if ($this->margins->removeElement($margin) && $margin->getParentEntity() === $this) {
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
