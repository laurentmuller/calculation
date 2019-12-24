<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pivot\Aggregator;

use App\Traits\MathTrait;

/**
 * Aggregator to get average of values.
 *
 * @author Laurent Muller
 */
class AverageAggregator extends Aggregator
{
    use MathTrait;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var float
     */
    protected $sum;

    /**
     * {@inheritdoc}
     */
    public function add($value): Aggregator
    {
        if ($value instanceof self) {
            $this->sum += $value->sum;
            $this->count += $value->count;
        } else {
            if (!empty($value)) {
                $this->sum += (float) $value;
            }
            if (null !== $value) {
                ++$this->count;
            }
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
    public function init(): Aggregator
    {
        $this->sum = 0;
        $this->count = 0;

        return $this;
    }
}
