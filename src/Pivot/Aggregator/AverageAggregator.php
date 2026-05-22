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
use App\Utils\FormatUtils;

/**
 * Aggregator to get average of values.
 */
class AverageAggregator extends AbstractCompositeAggregator
{
    use MathTrait;

    #[\Override]
    public function getFormattedResult(): string
    {
        return FormatUtils::formatAmount($this->getResult());
    }

    #[\Override]
    public function getResult(): float
    {
        return $this->safeDivide(
            $this->aggregators['sum']->getResult(),
            $this->aggregators['count']->getResult()
        );
    }

    #[\Override]
    public function getRoundResult(): float
    {
        return \round($this->getResult(), 2);
    }

    #[\Override]
    protected function createAggregators(): array
    {
        return [
            'sum' => new SumAggregator(),
            'count' => new CountAggregator(),
        ];
    }
}
