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

use App\Util\FormatUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a product.
 *
 * @author Laurent Muller
 *
 * @ORM\Table(name="sy_Product")
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @UniqueEntity(fields="description", message="product.unique_description")
 */
class Product extends AbstractCategoryItemEntity
{
    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="products")
     * @ORM\JoinColumn(name="category_id", nullable=false)
     * @Assert\NotNull
     */
    protected ?Category $category = null;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    private ?string $description = null;

    /**
     * The price.
     *
     * @ORM\Column(type="float", precision=2, options={"default" = 0})
     */
    private float $price = 0.0;

    /**
     * The supplier.
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private ?string $supplier = null;

    /**
     * Clone this product.
     *
     * @param string $description the new description
     */
    public function clone(?string $description = null): self
    {
        /** @var Product $copy */
        $copy = clone $this;

        if ($description) {
            $copy->setDescription($description);
        }

        return $copy;
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
     */
    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    /**
     * Set the description.
     *
     * @param string $description
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

    /**
     * Sets the supplier.
     */
    public function setSupplier(?string $supplier): self
    {
        $this->supplier = $this->trim($supplier);

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
            FormatUtils::formatAmount($this->price),
            $this->getCategoryCode(),
            $this->getGroupCode(),
        ];
    }
}
