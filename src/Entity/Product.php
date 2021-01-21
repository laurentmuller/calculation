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
class Product extends AbstractEntity
{
    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="products")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false)
     * @Assert\NotNull
     *
     * @var ?Category
     */
    protected $category;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $description;

    /**
     * The price.
     *
     * @ORM\Column(type="float", precision=2, options={"default": 0})
     *
     * @var float
     */
    protected $price;

    /**
     * The supplier.
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $supplier;

    /**
     * The unit.
     *
     * @ORM\Column(type="string", length=15, nullable=true)
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
        /** @var Product $copy */
        $copy = clone $this;

        if ($description) {
            $copy->setDescription($description);
        }

        return $copy;
    }

    /**
     * Get category.
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
     * Gets the group code.
     */
    public function getGroupCode(): ?string
    {
        return $this->category ? $this->category->getGroupCode() : null;
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
     * Get unit.
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * Set category.
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
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
     * Set unit.
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
            FormatUtils::formatAmount($this->price),
            $this->getCategoryCode(),
            $this->getGroupCode(),
        ];
    }
}
