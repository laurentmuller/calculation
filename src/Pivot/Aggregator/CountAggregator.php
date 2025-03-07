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
class CountAggregator extends AbstractAggregator
{
    private int $result = 0;

    #[\Override]
    public function add(mixed $value): static
    {
        if ($value instanceof self) {
            $this->result += $value->result;
        } elseif (null !== $value) {
            ++$this->result;
        }

        return $this;
    }

    #[\Override]
    public function getResult(): int
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
