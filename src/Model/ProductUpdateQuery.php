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

namespace App\Model;

use App\Entity\Category;
use App\Entity\Product;
use App\Util\FormatUtils;

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
    final public const UPDATE_FIXED = 'fixed';

    /**
     * Update products with a percent.
     */
    final public const UPDATE_PERCENT = 'percent';

    private bool $allProducts = true;
    private ?Category $category = null;
    private float $fixed = 0;
    private float $percent = 0;
    /** @var Product[] */
    private array $products = [];
    private bool $round = false;
    private bool $simulate = true;
    private string $type = self::UPDATE_PERCENT;

    /**
     * Gets the selected category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Gets the selected category code.
     */
    public function getCategoryCode(): ?string
    {
        return $this->category?->getCode();
    }

    /**
     * Gets the selected category identifier (the primary key).
     */
    public function getCategoryId(): int
    {
        return (int) $this->category?->getId();
    }

    /**
     * Gets the fixed amount to update for.
     */
    public function getFixed(): float
    {
        return $this->fixed;
    }

    /**
     * Gets the formatted value, depending on the percent state.
     */
    public function getFormattedValue(): string
    {
        return $this->isPercent() ? FormatUtils::formatPercent($this->getValue()) : FormatUtils::formatAmount($this->getValue());
    }

    /**
     * Gets the selected group code.
     */
    public function getGroupCode(): ?string
    {
        return $this->category?->getGroupCode();
    }

    /**
     * Gets the percent to update for.
     */
    public function getPercent(): float
    {
        return $this->percent;
    }

    /**
     * Gets the selected products to update.
     *
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * Gets the update type (percent or fixed amount).
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the update value (percent or fixed amount).
     */
    public function getValue(): float
    {
        return $this->isFixed() ? $this->fixed : $this->percent;
    }

    /**
     * Returns a value indicating if all products of the selected category must be updated.
     */
    public function isAllProducts(): bool
    {
        return $this->allProducts;
    }

    /**
     * Returns a value indicating if update is apply with the fixed amount.
     */
    public function isFixed(): bool
    {
        return self::UPDATE_FIXED === $this->type;
    }

    /**
     * Returns a value indicating if update is apply with the percent.
     */
    public function isPercent(): bool
    {
        return self::UPDATE_PERCENT === $this->type;
    }

    /**
     * Returns a value indicating if price is rounded (0.05).
     */
    public function isRound(): bool
    {
        return $this->round;
    }

    /**
     * Returns a value indicating if the update is simulated (no flush changes in the database).
     */
    public function isSimulate(): bool
    {
        return $this->simulate;
    }

    /**
     * Sets a value indicating if all products of the selected category must update.
     */
    public function setAllProducts(bool $allProducts): self
    {
        $this->allProducts = $allProducts;

        return $this;
    }

    /**
     * Sets the selected category.
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Sets the fixed amount to update for.
     */
    public function setFixed(?float $fixed): self
    {
        $this->fixed = (float) $fixed;

        return $this;
    }

    /**
     * Sets the percent to update for.
     */
    public function setPercent(?float $percent): self
    {
        $this->percent = (float) $percent;

        return $this;
    }

    /**
     * Sets the selected products to update.
     *
     * @param Product[] $products
     */
    public function setProducts(array $products): self
    {
        $this->products = $products;

        return $this;
    }

    /**
     * Sets a value indicating if price is rounded (0.05).
     */
    public function setRound(bool $round): self
    {
        $this->round = $round;

        return $this;
    }

    /**
     * Sets a value indicating if the update is simulated (no flush changes in the database).
     */
    public function setSimulate(bool $simulate): self
    {
        $this->simulate = $simulate;

        return $this;
    }

    /**
     * Sets the update type (percent or fixed amount).
     */
    public function setType(string $type): self
    {
        $this->type = match ($type) {
            self::UPDATE_PERCENT,
            self::UPDATE_FIXED => $type,
            default => $this->type,
        };

        return $this;
    }
}
