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

use App\Interfaces\ParentCalculationInterface;
use App\Repository\CalculationItemRepository;
use App\Traits\MathTrait;
use App\Traits\PositionTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an item of a calculation category.
 */
#[ORM\Entity(repositoryClass: CalculationItemRepository::class)]
#[ORM\Table(name: 'sy_CalculationItem')]
class CalculationItem extends AbstractEntity implements ParentCalculationInterface
{
    use MathTrait;
    use PositionTrait;

    /**
     * The parent's category.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false, onDelete: 'cascade')]
    protected ?CalculationCategory $category = null;

    /**
     * The description.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column()]
    protected ?string $description = null;

    /**
     * The price.
     */
    #[ORM\Column(scale: 2, options: ['default' => 0])]
    protected float $price = 0.0;

    /**
     * The quantity.
     */
    #[ORM\Column(scale: 2, options: ['default' => 0])]
    protected float $quantity = 0.0;

    /**
     * The unit.
     */
    #[Assert\Length(max: 15)]
    #[ORM\Column(length: 15, nullable: true)]
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
     * {@inheritDoc}
     */
    public function getCalculation(): ?Calculation
    {
        return $this->category?->getCalculation();
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
        return (string) $this->getDescription();
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
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * Returns if the price or the quantity are equal to zero.
     */
    public function isEmpty(): bool
    {
        return $this->isEmptyPrice() || $this->isEmptyQuantity();
    }

    /**
     * Returns if the price is equal to zero.
     */
    public function isEmptyPrice(): bool
    {
        return $this->isFloatZero($this->price);
    }

    /**
     * Returns if the quantity is equal to zero.
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
     */
    public function setUnit(?string $unit): self
    {
        $this->unit = $this->trim($unit);

        return $this;
    }
}
