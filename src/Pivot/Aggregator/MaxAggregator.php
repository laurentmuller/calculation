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
 * Aggregator to get the maximum value.
 */
class MaxAggregator extends AbstractFloatAggregator
{
    #[\Override]
    public function add(float|AbstractAggregator|int|null $value): static
    {
        if ($value instanceof self) {
            $this->result = \max($this->result, $value->result);
        } elseif (\is_numeric($value)) {
            $this->result = \max($this->result, (float) $value);
        }

        return $this;
    }

    #[\Override]
    protected function getInitialValue(): float
    {
        return (float) \PHP_INT_MIN;
    }
}
