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

/**
 * Aggregator to sum values.
 *
 * @author Laurent Muller
 */
class SumAggregator extends Aggregator
{
    /**
     * @var float
     */
    protected $result;

    /**
     * {@inheritdoc}
     */
    public function add($value): Aggregator
    {
        if ($value instanceof self) {
            $this->result += $value->result;
        } elseif (!empty($value)) {
            $this->result += (float) $value;
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
        return (float) $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function init(): Aggregator
    {
        $this->result = 0;

        return $this;
    }
}
