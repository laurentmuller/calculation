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

use App\Entity\Product;

/**
 * Contains result of updated products.
 *
 * @author Laurent Muller
 */
class ProductUpdateResult implements \Countable
{
    private ?string $code = null;
    private bool $percent = true;
    private array $products = [];
    private float $value = 0;

    /**
     * Add a product to the list of updated products.
     */
    public function addProduct(array $values): self
    {
        $this->products[] = $values;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return \count($this->products);
    }

    /**
     * Gets the selected category code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Gets the updated products.
     *
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * Gets the update value (percent or fixed amount).
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Returns a value indicating if update is apply with the percent.
     */
    public function isPercent(): bool
    {
        return $this->percent;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return !empty($this->products);
    }

    /**
     * Sets the selected category code.
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Sets the percent to update for.
     */
    public function setPercent(bool $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    /**
     * Sets the update value (percent or fixed amount).
     */
    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }
}
