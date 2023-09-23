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

/**
 * Contains result of updated products.
 *
 * @psalm-type ProductType = array{description: string|null, oldPrice: float, newPrice: float, delta: float}
 */
class ProductUpdateResult implements \Countable
{
    /**
     * @psalm-var ProductType[]
     */
    private array $products = [];

    /**
     * Add a product to the list of updated products.
     *
     * @psalm-param ProductType $values
     */
    public function addProduct(array $values): self
    {
        $this->products[] = $values;

        return $this;
    }

    public function count(): int
    {
        return \count($this->products);
    }

    /**
     * Gets the updated products.
     *
     * @psalm-return ProductType[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return [] !== $this->products;
    }
}
