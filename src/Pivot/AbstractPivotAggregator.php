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

namespace App\Pivot;

use App\Pivot\Aggregator\AggregatorInterface;
use App\Utils\StringUtils;

/**
 * Class with an aggregator function.
 */
abstract class AbstractPivotAggregator implements \JsonSerializable, \Stringable
{
    /**
     * @param AggregatorInterface                $aggregator the aggregator function
     * @param AggregatorInterface|int|float|null $value      the initial value
     */
    public function __construct(protected AggregatorInterface $aggregator, AggregatorInterface|int|float|null $value = null)
    {
        $this->addValue($value);
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s(%s)', StringUtils::getShortName($this), $this->getResult());
    }

    /**
     * Adds the given value to this aggregator.
     */
    public function addValue(AggregatorInterface|int|float|null $value): self
    {
        $this->aggregator->add($value);

        return $this;
    }

    /**
     * Gets the aggregator function.
     */
    public function getAggregator(): AggregatorInterface
    {
        return $this->aggregator;
    }

    /**
     * Gets the aggregator formatted value.
     */
    public function getFormattedValue(): string
    {
        return $this->aggregator->getFormattedResult();
    }

    /**
     * Gets the aggregator result.
     */
    public function getResult(): int|float
    {
        return $this->aggregator->getResult();
    }

    /**
     * Gets the aggregator rounded value.
     */
    public function getRoundResult(): int|float
    {
        return $this->aggregator->getRoundResult();
    }
}
