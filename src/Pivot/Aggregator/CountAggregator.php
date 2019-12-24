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

/**
 * Aggregator to count values.
 *
 * @author Laurent Muller
 */
class CountAggregator extends Aggregator
{
    /**
     * @var int
     */
    protected $result;

    /**
     * {@inheritdoc}
     */
    public function add($value): Aggregator
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
    public function init(): Aggregator
    {
        $this->result = 0;

        return $this;
    }
}
