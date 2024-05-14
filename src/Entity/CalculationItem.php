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

use App\Interfaces\ParentTimestampableInterface;
use App\Interfaces\PositionInterface;
use App\Repository\CalculationItemRepository;
use App\Traits\MathTrait;
use App\Traits\PositionTrait;
use App\Types\FixedFloatType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation item.
 *
 * @implements ParentTimestampableInterface<Calculation>
 */
#[ORM\Table(name: 'sy_CalculationItem')]
#[ORM\Entity(repositoryClass: CalculationItemRepository::class)]
class CalculationItem extends AbstractEntity implements ParentTimestampableInterface, PositionInterface
{
    use MathTrait;
    use PositionTrait;

    /**
     * The parent's category.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false, onDelete: 'cascade')]
    private ?CalculationCategory $category = null;

    /**
     * The description.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    private ?string $description = null;

    /**
     * The price.
     */
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $price = 0.0;

    /**
     * The quantity.
     */
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $quantity = 0.0;

    /**
     * The unit.
     */
    #[Assert\Length(max: 15)]
    #[ORM\Column(length: 15, nullable: true)]
    private ?string $unit = null;

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

    public function getDisplay(): string
    {
        return (string) $this->getDescription();
    }

    public function getParentEntity(): ?Calculation
    {
        return $this->category?->getParentEntity();
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
     * Returns if the price or the quantity is equal to zero.
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
