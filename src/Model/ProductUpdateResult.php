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

/**
 * Contains result of updated products.
 *
 * @author Laurent Muller
 */
class ProductUpdateResult implements \Countable
{
    private array $products = [];

    public function addProduct(array $values): self
    {
        $this->products[] = $values;

        return $this;
    }

    public function count(): int
    {
        return \count($this->products);
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function isValid(): bool
    {
        return !empty($this->products);
    }
}
