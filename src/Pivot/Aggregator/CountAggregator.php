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

namespace App\Pivot\Aggregator;

/**
 * Aggregator to count values.
 */
class CountAggregator extends AbstractIntAggregator
{
    #[\Override]
    public function add(AggregatorInterface|int|float|null $value): static
    {
        if ($value instanceof self) {
            return $this->updateValue($value->result);
        }
        if (null !== $value) {
            return $this->updateValue(1);
        }

        return $this;
    }

    private function updateValue(int $value): static
    {
        $this->result += $value;

        return $this;
    }
}
