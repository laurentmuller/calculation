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
    private ?string $code = null;
    private bool $percent = true;
    private array $products = [];
    private float $value = 0;

    public function addProduct(array $values): self
    {
        $this->products[] = $values;

        return $this;
    }

    public function count(): int
    {
        return \count($this->products);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function isPercent(): bool
    {
        return $this->percent;
    }

    public function isValid(): bool
    {
        return !empty($this->products);
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function setPercent(bool $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }
}
