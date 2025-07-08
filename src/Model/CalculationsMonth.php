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

readonly class CalculationsMonth implements \Countable
{
    public CalculationsTotal $total;

    public function __construct(
        /** @var CalculationsMonthItem[] $items */
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
     */
    private function generate(): CalculationsTotal
    {
        $count = \array_reduce(
            $this->items,
            static fn (int $carry, CalculationsMonthItem $item): int => $carry + $item->count,
            0
        );
        $total = \array_reduce(
            $this->items,
            static fn (float $carry, CalculationsMonthItem $item): float => $carry + $item->total,
            0.0
        );
        $items = \array_reduce(
            $this->items,
            static fn (float $carry, CalculationsMonthItem $item): float => $carry + $item->items,
            0.0
        );

        return new CalculationsTotal($count, $items, $total);
    }
}
