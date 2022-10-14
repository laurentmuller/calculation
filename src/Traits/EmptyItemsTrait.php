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

namespace App\Traits;

/**
 * Trait to format empty items for table, PDF report and Excel document.
 */
trait EmptyItemsTrait
{
    /**
     * @psalm-param array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}> $items
     */
    public function formatItems(array $items): string
    {
        $priceLabel = $this->getPriceLabel();
        $quantityLabel = $this->getQuantityLabel();
        $result = \array_map(function (array $item) use ($priceLabel, $quantityLabel): string {
            $founds = [];
            if ($this->isFloatZero($item['price'])) {
                $founds[] = $priceLabel;
            }
            if ($this->isFloatZero($item['quantity'])) {
                $founds[] = $quantityLabel;
            }

            return \sprintf('%s (%s)', $item['description'], \implode(', ', $founds));
        }, $items);

        return \implode($this->getItemsSeparator(), $result);
    }

    /**
     * Gets the separator used to implode items.
     */
    protected function getItemsSeparator(): string
    {
        return "\n";
    }

    abstract protected function getPriceLabel(): string;

    abstract protected function getQuantityLabel(): string;
}
