<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents a group of categories.
 *
 * @author Laurent Muller
 *
 * @ORM\Table(name="sy_Group")
 * @ORM\Entity(repositoryClass="App\Repository\GroupRepository")
 * @UniqueEntity(fields="code", message="group.unique_code")
 */
class Group extends AbstractEntity
{
    /**
     * The categories.
     *
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="group", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"code" = "ASC"})
     *
     * @var Collection|Category[]
     * @psalm-var Collection<int, Category>
     */
    private $categories;

    /**
     * The unique code.
     *
     * @ORM\Column(type="string", length=30, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     *
     * @var string
     */
    private $code;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    private $description;

    /**
     * The margins.
     *
     * @ORM\OneToMany(targetEntity=GroupMargin::class, mappedBy="group", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"minimum" = "ASC"})
     * @Assert\Valid
     *
     * @var Collection|GroupMargin[]
     * @psalm-var Collection<int, GroupMargin>
     */
    private $margins;

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
     * @param string $code the new code
     */
    public function clone(?string $code = null): self
    {
        /** @var Group $copy */
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
     * Gets the number of margins.
     */
    public function countMargins(): int
    {
        return $this->margins->count();
    }

    /**
     * Finds the group margin for the given amount.
     *
     * @param float $amount the amount to get group margin for
     *
     * @return GroupMargin|null the group margin, if found; null otherwise
     *
     * @see \App\Entity\Group::containsAmount()
     */
    public function findMargin(float $amount): ?GroupMargin
    {
        /** @var GroupMargin $margin */
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
     * @param float $amount the amount to get percent
     *
     * @return float the percent of the group margin, if found; 0 otherwise
     *
     * @see Group::findMargin()
     */
    public function findPercent(float $amount): float
    {
        $margin = $this->findMargin($amount);

        return $margin ? $margin->getMargin() : 0;
    }

    /**
     * Get categories.
     *
     * @return Collection|Category[]
     * @psalm-return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     *
     * @see \App\Entity\AbstractEntity::getDisplay()
     */
    public function getDisplay(): string
    {
        return $this->getCode();
    }

    /**
     * Get margins.
     *
     * @return Collection|GroupMargin[]
     * @psalm-return Collection<int, GroupMargin>
     */
    public function getMargins(): Collection
    {
        return $this->margins;
    }

    /**
     * Returns if this group contains one or more categories.
     *
     * @return bool true if contains products
     */
    public function hasCategories(): bool
    {
        return !$this->categories->isEmpty();
    }

    /**
     * Returns if this group contains one or more margins.
     *
     * @return bool true if contains margins
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
        if ($this->categories->removeElement($category)) {
            if ($category->getGroup() === $this) {
                $category->setGroup(null);
            }
        }

        return $this;
    }

    /**
     * Remove a margin.
     */
    public function removeMargin(GroupMargin $margin): self
    {
        if ($this->margins->removeElement($margin)) {
            if ($margin->getGroup() === $this) {
                $margin->setGroup(null);
            }
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
     *
     * @param string $description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context): void
    {
        // margins?
        $margins = $this->getMargins();
        if ($margins->isEmpty()) {
            return;
        }

        $lastMin = null;
        $lastMax = null;

        /** @var GroupMargin $margin */
        foreach ($margins as $key => $margin) {
            // get values
            $min = $margin->getMinimum();
            $max = $margin->getMaximum();

            if (null === $lastMin) {
                // first time
                $lastMin = $min;
                $lastMax = $max;
            } elseif ($min <= $lastMin) {
                // the minimum is smaller than the previous maximum
                $context->buildViolation('abstract_margin.minimum_overlap')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($min >= $lastMin && $min < $lastMax) {
                // the minimum is overlapping the previous margin
                $context->buildViolation('abstract_margin.minimum_overlap')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($max > $lastMin && $max < $lastMax) {
                // the maximum is overlapping the previous margin
                $context->buildViolation('abstract_margin.maximum_overlap')
                    ->atPath("margins[$key].maximum")
                    ->addViolation();
                break;
            } elseif ($min !== $lastMax) {
                // the minimum is not equal to the previous maximum
                $context->buildViolation('abstract_margin.minimum_discontinued')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } else {
                // copy
                $lastMin = $min;
                $lastMax = $max;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->code,
            $this->description,
        ];
    }
}
