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
use App\Repository\ProductRepository;
use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a product.
 *
 * @implements ComparableInterface<Product>
 */
#[ORM\Table(name: 'sy_Product')]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_product_description', columns: ['description'])]
#[UniqueEntity(fields: 'description', message: 'product.unique_description')]
class Product extends AbstractCategoryItemEntity implements ComparableInterface
{
    /** The parent's category. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    public ?Category $category = null;

    /** The description. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(unique: true)]
    public ?string $description = null {
        set => StringUtils::trim($value);
    }

    /** The price. */
    #[Assert\NotBlank]
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $price = 0.0 {
        set => $this->round($value);
    }

    /**
     * Clone this product.
     *
     * @param ?string $description the new description
     */
    public function clone(?string $description = null): self
    {
        $copy = clone $this;
        if (StringUtils::isString($description)) {
            $copy->withDescription($description);
        }

        return $copy;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->description, (string) $other->description);
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->description;
    }

    /**
     * Set the description.
     */
    public function withDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the price.
     */
    public function withPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }
}
