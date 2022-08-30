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
 */
class ProductUpdateResult implements \Countable
{
    /**
     * @var array<array{description: string|null, oldPrice: float, newPrice: float}>
     */
    private array $products = [];
    private bool $simulate = true;

    /**
     * Add a product to the list of updated products.
     *
     * @param array{description: string|null, oldPrice: float, newPrice: float} $values
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
     * Gets the updated products.
     *
     * @return array<array{description: string|null, oldPrice: float, newPrice: float}>
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function isSimulate(): bool
    {
        return $this->simulate;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return !empty($this->products);
    }

    public function setSimulate(bool $simulate): self
    {
        $this->simulate = $simulate;

        return $this;
    }
}
