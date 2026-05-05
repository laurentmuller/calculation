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
 * @template T of ChartDataItem
 */
abstract readonly class ChartData implements \Countable
{
    public ChartDataItem $total;

    public function __construct(
        /** @var T[] $items */
        public array $items
    ) {
        $this->total = $this->generateTotalItem();
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * Generate a new total instance for these items.
     */
    protected function generateTotalItem(): ChartDataItem
    {
        return new ChartDataItem(
            count: $this->getItemsSum('count'),
            items: $this->getItemsSum('items'),
            total: $this->getItemsSum('total')
        );
    }

    /**
     * @phpstan-return ($key is 'count' ? int : float)
     */
    private function getItemsSum(string $key): int|float
    {
        return \array_sum(\array_column($this->items, $key));
    }
}
