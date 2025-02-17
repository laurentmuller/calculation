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

use App\Traits\MathTrait;

/**
 * Aggregator to get average of values.
 */
class AverageAggregator extends AbstractAggregator
{
    use MathTrait;

    private int $count = 0;

    private float $sum = 0.0;

    #[\Override]
    public function add(mixed $value): static
    {
        if ($value instanceof self) {
            $this->sum += $value->sum;
            $this->count += $value->count;
        } elseif (null !== $value) {
            ++$this->count;
            $this->sum += (float) $value;
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
        return $this->safeDivide($this->sum, $this->count);
    }

    #[\Override]
    public function init(): static
    {
        $this->sum = 0;
        $this->count = 0;

        return $this;
    }
}
