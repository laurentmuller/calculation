<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
 * Represents a category of prodcuts.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 * @ORM\Table(name="sy_Category")
 * @UniqueEntity(fields="code", message="category.unique_code")
 */
class Category extends BaseEntity
{
    /**
     * The unique code.
     *
     * @ORM\Column(name="code", type="string", length=30, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     *
     * @var string
     */
    protected $code;

    /**
     * The description.
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $description;

    /**
     * The margins.
     *
     * @ORM\OneToMany(
     *     targetEntity="CategoryMargin",
     *     mappedBy="category",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @ORM\OrderBy({"minimum": "ASC"})
     * @Assert\Valid
     *
     * @var Collection|CategoryMargin[]
     */
    protected $margins;

    /**
     * The list of products that fall into this category.
     *
     * @ORM\OneToMany(targetEntity="Product", mappedBy="category", cascade={"persist"}, orphanRemoval=true)
     *
     * @var Collection|Product[]
     */
    protected $products;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->margins = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    /**
     * Add margin.
     *
     * @param \App\Entity\CategoryMargin $margin
     *
     * @return Category
     */
    public function addMargin(CategoryMargin $margin): self
    {
        if (!$this->margins->contains($margin)) {
            $this->margins->add($margin);
            $margin->setCategory($this);
        }

        return $this;
    }

    /**
     * Add product.
     *
     * @param \App\Entity\Product $product
     *
     * @return Category
     */
    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }

        return $this;
    }

    /**
     * Gets the number of margins.
     */
    public function countMargins(): int
    {
        return $this->margins->count();
    }

    /**
     * Gets the number of prodcuts.
     */
    public function countProducts(): int
    {
        return $this->products->count();
    }

    /**
     * Finds the category margin for the given amount.
     *
     * @param float $amount the amount to get category margin for
     *
     * @return \App\Entity\CategoryMargin|null the category margin, if found; NULL otherwise
     *
     * @see CategoryMargin::contains()
     */
    public function findMargin(float $amount): ?CategoryMargin
    {
        foreach ($this->margins as $margin) {
            if ($margin->containsAmount($amount)) {
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
     * @return float the percent of the category margin, if found; 0 otherwise
     *
     * @see Category::findMargin()
     */
    public function findPercent(float $amount): float
    {
        $margin = $this->findMargin($amount);

        return $margin ? $margin->getMargin() : 0;
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
     * @see \App\Entity\BaseEntity::getDisplay()
     */
    public function getDisplay(): string
    {
        return $this->getCode();
    }

    /**
     * Get margins.
     *
     * @return Collection|CategoryMargin[]
     */
    public function getMargins(): Collection
    {
        return $this->margins;
    }

    /**
     * Get products.
     *
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * Returns if this category contains one or more margins.
     *
     * @return bool true if contains margins
     */
    public function hasMargins(): bool
    {
        return !$this->margins->isEmpty();
    }

    /**
     * Returns if this category contains one or more products.
     *
     * @return bool true if contains products
     */
    public function hasProducts(): bool
    {
        return !$this->products->isEmpty();
    }

    /**
     * Remove margin.
     *
     * @param \App\Entity\CategoryMargin $margin
     */
    public function removeMargin(CategoryMargin $margin): self
    {
        if ($this->margins->contains($margin) && $this->margins->removeElement($margin)) {
            if ($margin->getCategory() === $this) {
                $margin->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * Remove product.
     *
     * @param \App\Entity\Product $product
     */
    public function removeProduct(Product $product): self
    {
        if ($this->products->contains($product) && $this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode(?string $code): self
    {
        $this->code = $this->trim($code);

        return $this;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $this->trim($description);

        return $this;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context the execution context
     */
    public function validate(ExecutionContextInterface $context): void
    {
        // get margins
        $margins = $this->getMargins();
        if ($margins->isEmpty()) {
            return;
        }

        // validate
        $lastMin = null;
        $lastMax = null;
        foreach ($margins as $key => $margin) {
            // get values
            $min = $margin->getMinimum();
            $max = $margin->getMaximum();

            if (null === $lastMin) {
                // first time
                $lastMin = $min;
                $lastMax = $max;
            } elseif ($min <= $lastMin) {
                $context->buildViolation('abstract_margin.minimum_overlap')
                    ->atPath('margins[' . $key . '].minimum')
                    ->addViolation();
                break;
            } elseif ($min >= $lastMin && $min < $lastMax) {
                $context->buildViolation('abstract_margin.minimum_overlap')
                    ->atPath('margins[' . $key . '].minimum')
                    ->addViolation();
                break;
            } elseif ($max > $lastMin && $max < $lastMax) {
                $context->buildViolation('abstract_margin.maximum_overlap')
                    ->atPath('margins[' . $key . '].maximum')
                    ->addViolation();
                break;
            } else {
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
