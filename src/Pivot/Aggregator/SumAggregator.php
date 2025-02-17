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
class SumAggregator extends AbstractAggregator
{
    private float $result = 0.0;

    #[\Override]
    public function add(mixed $value): static
    {
        if ($value instanceof self) {
            $this->result += $value->result;
        } elseif (null !== $value) {
            $this->result += (float) $value;
        }

        return $this;
    }

    #[\Override]
    public function getFormattedResult(): float
    {
        return \round($this->getResult(), 2);
    }

    #[\Override]
    public function getResult(): float
    {
        return $this->result;
    }

    #[\Override]
    public function init(): static
    {
        $this->result = 0;

        return $this;
    }
}
