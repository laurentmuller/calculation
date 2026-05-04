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
        $values = \array_reduce(
            $this->items,
            static function (array $carry, ChartDataItem $item): array {
                $carry['count'] += $item->count;
                $carry['items'] += $item->items;
                $carry['total'] += $item->total;

                return $carry;
            },
            [
                'count' => 0,
                'items' => 0.0,
                'total' => 0.0,
            ]
        );

        return new ChartDataItem(
            count: $values['count'],
            items: $values['items'],
            total: $values['total']
        );
    }
}
