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

use App\Traits\NumberFormatterTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a product.
 *
 * @ORM\Table(name="sy_Product")
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @UniqueEntity(fields="description", message="product.unique_description")
 */
class Product extends BaseEntity
{
    use NumberFormatterTrait;

    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="products")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false)
     *
     * @var ?Category
     */
    protected $category;

    /**
     * The description.
     *
     * @ORM\Column(name="description", type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $description;

    /**
     * The price.
     *
     * @ORM\Column(name="price", type="float", precision=2, options={"default": 0})
     *
     * @var float
     */
    protected $price;

    /**
     * The supplier.
     *
     * @ORM\Column(name="supplier", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $supplier;

    /**
     * The unit.
     *
     * @ORM\Column(name="unit", type="string", length=15, nullable=true)
     * @Assert\Length(max=15)
     *
     * @var string
     */
    protected $unit;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // default values
        $this->price = 0.0;
    }

    /**
     * Clone this product.
     *
     * @param string $description the new description
     */
    public function clone(?string $description = null): self
    {
        if ($description) {
            return (clone $this)->setDescription($description);
        }

        return clone $this;
    }

    /**
     * Get category.
     *
     * @return \App\Entity\Category
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Gets the category code.
     */
    public function getCategoryCode(): ?string
    {
        return $this->category ? $this->category->getCode() : null;
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
     */
    public function getDisplay(): string
    {
        return $this->getDescription();
    }

    /**
     * Get price.
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Gets the supplier.
     *
     * @return string
     */
    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    /**
     * Get unit.
     *
     * @return string
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * Set category.
     *
     * @param \App\Entity\Category $category
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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
     * Set price.
     */
    public function setPrice(float $price): self
    {
        $this->price = $this->round($price);

        return $this;
    }

    /**
     * Sets the supplier.
     *
     * @param string $supplier
     */
    public function setSupplier(?string $supplier): self
    {
        $this->supplier = $this->trim($supplier);

        return $this;
    }

    /**
     * Set unit.
     *
     * @param string $unit
     */
    public function setUnit(?string $unit): self
    {
        $this->unit = $this->trim($unit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->description,
            $this->unit,
            $this->supplier,
            $this->localeAmount($this->price),
            $this->category->getCode(),
        ];
    }
}
