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
 * Aggregator to get the minimum value or 0.0 if no value is added.
 */
class MinAggregator extends AbstractFloatAggregator
{
    private bool $initialized = false;

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

    #[\Override]
    public function initialize(): static
    {
        $this->initialized = false;

        return parent::initialize();
    }

    private function updateValue(float $value): static
    {
        if (!$this->initialized) {
            $this->result = \PHP_INT_MAX;
            $this->initialized = true;
        }
        $this->result = \min($this->result, $value);

        return $this;
    }
}
