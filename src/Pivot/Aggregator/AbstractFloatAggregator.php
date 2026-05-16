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
 * Abstract aggregator for float values.
 */
abstract class AbstractFloatAggregator extends AbstractAggregator
{
    protected float $result;

    public function __construct(float|AbstractAggregator|int|null $value = null)
    {
        $this->result = $this->getInitialValue();
        parent::__construct($value);
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
    public function initialize(): static
    {
        $this->result = $this->getInitialValue();

        return $this;
    }

    /**
     * Gets the initial value.
     */
    protected function getInitialValue(): float
    {
        return 0.0;
    }
}
