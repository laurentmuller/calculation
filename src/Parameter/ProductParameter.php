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

namespace App\Parameter;

use App\Attribute\Parameter;
use App\Entity\Product;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Product parameter.
 */
class ProductParameter implements EntityParameterInterface
{
    #[Parameter('default_product_edit', false)]
    private bool $edit = false;

    #[Parameter('default_product')]
    private ?Product $product = null;

    #[Assert\GreaterThanOrEqual(0.0)]
    #[Parameter('default_product_quantity', 0.0)]
    private float $quantity = 0.0;

    public static function getCacheKey(): string
    {
        return 'parameter_product';
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function isEdit(): bool
    {
        return $this->edit;
    }

    public function setEdit(bool $edit): self
    {
        $this->edit = $edit;

        return $this;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }
}
