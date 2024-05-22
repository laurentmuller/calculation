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

use App\Interfaces\ComparableInterface;
use App\Interfaces\TimestampableInterface;
use App\Repository\ProductRepository;
use App\Traits\TimestampableTrait;
use App\Types\FixedFloatType;
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
class Product extends AbstractCategoryItemEntity implements ComparableInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * The parent's category.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    protected ?Category $category = null;

    /**
     * The description.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(unique: true)]
    private ?string $description = null;

    /**
     * The price.
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $price = 0.0;

    /**
     * Clone this product.
     *
     * @param ?string $description the new description
     */
    public function clone(?string $description = null): self
    {
        $copy = clone $this;
        if (StringUtils::isString($description)) {
            $copy->setDescription($description);
        }

        return $copy;
    }

    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->getDescription(), (string) $other->getDescription());
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

    /**
     * Get price.
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Set the description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $this->trim($description);

        return $this;
    }

    /**
     * Set the price.
     */
    public function setPrice(float $price): self
    {
        $this->price = $this->round($price);

        return $this;
    }
}
