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

use App\Traits\MathTrait;

class CalculationsState implements \Countable
{
    use MathTrait;

    public readonly CalculationsTotal $total;

    public function __construct(
        /** @var CalculationsStateItem[] $items */
        public array $items,
    ) {
        $this->total = $this->generate();
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * Generate a new total instance for these items.
     *
     * The percentages of these items are also updated.
     */
    private function generate(): CalculationsTotal
    {
        $count = \array_reduce(
            $this->items,
            static fn (int $carry, CalculationsStateItem $item): int => $carry + $item->count,
            0
        );
        $items = \array_reduce(
            $this->items,
            static fn (float $carry, CalculationsStateItem $item): float => $carry + $item->items,
            0.0
        );
        $total = \array_reduce(
            $this->items,
            static fn (float $carry, CalculationsStateItem $item): float => $carry + $item->total,
            0.0
        );

        foreach ($this->items as $item) {
            $item->calculationsPercent = $this->round($this->safeDivide($item->count, $count), 4);
            $item->totalPercent = $this->round($this->safeDivide($item->total, $total), 4);
        }

        return new CalculationsTotal($count, $items, $total);
    }
}
