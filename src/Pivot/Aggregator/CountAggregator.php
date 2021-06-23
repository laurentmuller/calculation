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
 * Aggregator to count values.
 *
 * @author Laurent Muller
 */
class CountAggregator extends AbstractAggregator
{
    protected int $result = 0;

    /**
     * {@inheritdoc}
     */
    public function add($value): AbstractAggregator
    {
        if ($value instanceof self) {
            $this->result += $value->result;
        } elseif (null !== $value) {
            ++$this->result;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return (int) $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function init(): AbstractAggregator
    {
        $this->result = 0;

        return $this;
    }
}
