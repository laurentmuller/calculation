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

use App\Doctrine\FixedFloatType;
use App\Interfaces\ComparableInterface;
use App\Interfaces\ParentTimestampableInterface;
use App\Interfaces\PositionInterface;
use App\Repository\CalculationItemRepository;
use App\Traits\MathTrait;
use App\Traits\PositionTrait;
use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation item.
 *
 * @implements ParentTimestampableInterface<Calculation>
 * @implements ComparableInterface<CalculationItem>
 */
#[ORM\Table(name: 'sy_CalculationItem')]
#[ORM\Entity(repositoryClass: CalculationItemRepository::class)]
class CalculationItem extends AbstractEntity implements ComparableInterface, ParentTimestampableInterface, PositionInterface
{
    use MathTrait;
    use PositionTrait;

    /** The parent's category. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false, onDelete: 'cascade')]
    public ?CalculationCategory $category = null;

    /** The description. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    public ?string $description = null {
        set => StringUtils::trim($value);
    }

    /** The price. */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $price = 0.0 {
        set => $this->round($value);
    }

    /** The quantity. */
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $quantity = 0.0 {
        set => $this->round($value);
    }

    /**
     * Gets the total.
     *
     * This is the quantity multiplied by the price.
     */
    public float $total {
        get => $this->round($this->quantity * $this->price);
    }

    /** The unit. */
    #[Assert\Length(max: 15)]
    #[ORM\Column(length: 15, nullable: true)]
    public ?string $unit = null {
        set => StringUtils::trim($value);
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->description, (string) $other->description);
    }

    /**
     * Create a calculation item from the given product.
     *
     * @param Product $product the product to copy values from
     */
    public static function create(Product $product): self
    {
        return new self()
            ->withDescription($product->description)
            ->withPrice($product->price)
            ->withUnit($product->unit);
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->description;
    }

    #[\Override]
    public function getParentEntity(): ?Calculation
    {
        return $this->category?->getParentEntity();
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
    public function withCategory(?CalculationCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Set description.
     */
    public function withDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set price.
     */
    public function withPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Set quantity.
     */
    public function withQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Set unit.
     */
    public function withUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }
}
