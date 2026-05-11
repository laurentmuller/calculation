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

    private CountAggregator $countAggregator;
    private SumAggregator $sumAggregator;

    public function __construct(AbstractAggregator|int|float|null $value = null)
    {
        $this->countAggregator = new CountAggregator();
        $this->sumAggregator = new SumAggregator();
        parent::__construct($value);
    }

    #[\Override]
    public function add(AbstractAggregator|int|float|null $value): static
    {
        if ($value instanceof self) {
            $this->countAggregator->add($value->countAggregator);
            $this->sumAggregator->add($value->sumAggregator);
        } elseif (null !== $value) {
            $this->countAggregator->add($value);
            $this->sumAggregator->add($value);
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
        return $this->safeDivide(
            $this->sumAggregator->getResult(),
            $this->countAggregator->getResult()
        );
    }

    #[\Override]
    public function init(): static
    {
        $this->countAggregator->init();
        $this->sumAggregator->init();

        return $this;
    }
}
