<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pivot\Aggregator;

use App\Traits\MathTrait;

/**
 * Aggregator to get average of values.
 *
 * @author Laurent Muller
 */
class AverageAggregator extends AbstractAggregator
{
    use MathTrait;

    protected int $count = 0;

    protected float $sum = 0.0;

    /**
     * {@inheritdoc}
     */
    public function add($value): AbstractAggregator
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

    /**
     * {@inheritdoc}
     */
    public function getFormattedResult()
    {
        return \round($this->getResult(), 2);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->safeDivide($this->sum, $this->count);
    }

    /**
     * {@inheritdoc}
     */
    public function init(): AbstractAggregator
    {
        $this->sum = 0;
        $this->count = 0;

        return $this;
    }
}
