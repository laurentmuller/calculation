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

namespace App\Pivot;

use App\Pivot\Aggregator\Aggregator;
use App\Utils\Utils;

/**
 * Class with an aggregator function.
 *
 * @author Laurent Muller
 */
abstract class PivotAggregator implements \JsonSerializable
{
    /**
     * The aggregator function.
     *
     * @var Aggregator
     */
    protected $aggregator;

    /**
     * Constructor.
     *
     * @param Aggregator $aggregator the aggregator function
     * @param mixed      $value      the initial value
     */
    public function __construct(Aggregator $aggregator, $value = null)
    {
        $this->aggregator = $aggregator;
        $this->addValue($value);
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);
        $value = $this->getValue();

        return \sprintf('%s(%s)', $name, $value);
    }

    /**
     * Adds the given value to this value.
     *
     * @param mixed $value the value to add
     *
     * @return self
     */
    public function addValue($value)
    {
        $this->aggregator->add($value);

        return $this;
    }

    /**
     * Gets the aggregator function.
     */
    public function getAggregator(): Aggregator
    {
        return $this->aggregator;
    }

    /**
     * Gets the value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->aggregator->getResult();
    }
}
