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

use App\Traits\MathTrait;
use App\Traits\PositionTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an item in a calculation category.
 *
 * @author Laurent Muller
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationItemRepository")
 * @ORM\Table(name="sy_CalculationItem")
 */
class CalculationItem extends AbstractEntity
{
    use MathTrait;
    use PositionTrait;

    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity=CalculationCategory::class, inversedBy="items")
     * @ORM\JoinColumn(name="category_id", onDelete="CASCADE", nullable=false)
     */
    protected ?CalculationCategory $category = null;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    protected ?string $description = null;

    /**
     * The price.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    protected float $price = 0.0;

    /**
     * The quantity.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     */
    protected float $quantity = 0.0;

    /**
     * The unit.
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     * @Assert\Length(max=15)
     */
    protected ?string $unit = null;

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
        return $this->category ? $this->category->getCalculation() : null;
    }

    /**
     * Get the parent's category.
     */
    public function getCategory(): ?CalculationCategory
    {
        return $this->category;
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
     * Get quantity.
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * Gets the total of this item.
     *
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
     * Set the parent's category.
     */
    public function setCategory(?CalculationCategory $category): self
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
