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

namespace App\Model;

use App\Entity\Category;
use App\Entity\Product;

/**
 * Contains query parameters to update products.
 *
 * @author Laurent Muller
 */
class ProductUpdateQuery
{
    /**
     * Update products with a fixed amount.
     */
    public const UPDATE_FIXED = 'fixed';

    /**
     * Update products with a percent.
     */
    public const UPDATE_PERCENT = 'percent';

    private bool $allProducts = true;
    private ?Category $category = null;
    private float $fixed = 0;
    private float $percent = 0;
    private array $products = [];
    private bool $round = false;
    private bool $simulated = true;
    private string $type = self::UPDATE_PERCENT;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getCategoryCode(): ?string
    {
        return null !== $this->category ? $this->category->getCode() : null;
    }

    public function getCategoryId(): int
    {
        return null !== $this->category ? $this->category->getId() : 0;
    }

    public function getFixed(): float
    {
        return $this->fixed;
    }

    public function getGroupCode(): ?string
    {
        return null !== $this->category ? $this->category->getGroup()->getCode() : null;
    }

    public function getPercent(): float
    {
        return $this->percent;
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): float
    {
        return $this->isFixed() ? $this->fixed : $this->percent;
    }

    public function isAllProducts(): bool
    {
        return $this->allProducts;
    }

    public function isFixed(): bool
    {
        return self::UPDATE_FIXED === $this->type;
    }

    public function isPercent(): bool
    {
        return self::UPDATE_PERCENT === $this->type;
    }

    public function isRound(): bool
    {
        return $this->round;
    }

    public function isSimulated(): bool
    {
        return $this->simulated;
    }

    public function setAllProducts(bool $allProducts): self
    {
        $this->allProducts = $allProducts;

        return $this;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function setFixed(?float $fixed): self
    {
        $this->fixed = (float) $fixed;

        return $this;
    }

    public function setPercent(?float $percent): self
    {
        $this->percent = (float) $percent;

        return $this;
    }

    /**
     * @param Product[] $products
     */
    public function setProducts(array $products): self
    {
        $this->products = $products;

        return $this;
    }

    public function setRound(bool $round): self
    {
        $this->round = $round;

        return $this;
    }

    public function setSimulated(bool $simulated): self
    {
        $this->simulated = $simulated;

        return $this;
    }

    public function setType(string $type): self
    {
        switch ($type) {
            case self::UPDATE_FIXED:
            case self::UPDATE_PERCENT:
                $this->type = $type;
                break;
        }

        return $this;
    }
}
