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

use App\Traits\MathTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation with groups and products.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationItemRepository")
 * @ORM\Table(name="sy_CalculationItem")
 */
class CalculationItem extends BaseEntity
{
    use MathTrait;

    /**
     * The description.
     *
     * @ORM\Column(name="description", type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $description;

    /**
     * The parent's group.
     *
     * @ORM\ManyToOne(
     *     targetEntity="CalculationGroup",
     *     inversedBy="items"
     * )
     * @ORM\JoinColumn(
     *     name="group_id",
     *     referencedColumnName="id",
     *     onDelete="CASCADE",
     *     nullable=false
     * )
     *
     * @var ?CalculationGroup
     */
    protected $group;

    /**
     * The price.
     *
     * @ORM\Column(name="price", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $price;

    /**
     * The quantity.
     *
     * @ORM\Column(name="quantity", type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    protected $quantity;

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
        $this->price = $this->quantity = 0;
    }

    /**
     * Create a calculation item from the given product.
     *
     * @param Product $product the product to copy values from
     */
    public static function create(Product $product): self
    {
        $item = new self();
        $item->setDescription($product->getDescription())
            ->setPrice($product->getPrice())
            ->setUnit($product->getUnit());

        return $item;
    }

    /**
     * Gets the parent's calculation.
     *
     * @return Calculation|null the calculation, if any; null otherwise
     */
    public function getCalculation(): ?Calculation
    {
        return $this->group ? $this->group->getCalculation() : null;
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
        return $this->description;
    }

    /**
     * Get the parent's group.
     *
     * @return CalculationGroup
     */
    public function getGroup(): ?CalculationGroup
    {
        return $this->group;
    }

    /**
     * Get price.
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Get quantity.
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * Gets the total of this item.
     * This is the quantity multiplied by the price.
     */
    public function getTotal(): float
    {
        return $this->round($this->quantity * $this->price);
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
     * Returns if the price or the quantity are equal to zero.
     *
     * @return bool true if the price or the quantity are equal to zero
     */
    public function isEmpty(): bool
    {
        return $this->isEmptyPrice() || $this->isEmptyQuantity();
    }

    /**
     * Returns if the price is equal to zero.
     *
     * @return bool true if equal to zero
     */
    public function isEmptyPrice(): bool
    {
        return $this->isFloatZero($this->price);
    }

    /**
     * Returns if the the quantity is equal to zero.
     *
     * @return bool true if equal to zero
     */
    public function isEmptyQuantity(): bool
    {
        return $this->isFloatZero($this->quantity);
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
     * Set the  parent's group.
     *
     * @param CalculationGroup $group
     */
    public function setGroup(?CalculationGroup $group): self
    {
        $this->group = $group;

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
     * Set quantity.
     */
    public function setQuantity(float $quantity): self
    {
        $this->quantity = $this->round($quantity);

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
}
