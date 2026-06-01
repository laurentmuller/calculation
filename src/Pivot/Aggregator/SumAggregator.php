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
 * Aggregator to sum values.
 */
class SumAggregator extends AbstractFloatAggregator
{
    #[\Override]
    public function add(AggregatorInterface|int|float|null $value): static
    {
        if ($value instanceof self) {
            return $this->updateValue($value->result);
        }
        if (\is_float($value) || \is_int($value)) {
            return $this->updateValue((float) $value);
        }

        return $this;
    }

    private function updateValue(float $value): static
    {
        $this->result += $value;

        return $this;
    }
}
