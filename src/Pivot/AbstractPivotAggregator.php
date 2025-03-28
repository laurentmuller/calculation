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

use App\Pivot\Aggregator\AbstractAggregator;
use App\Utils\StringUtils;

/**
 * Class with an aggregator function.
 */
abstract class AbstractPivotAggregator implements \JsonSerializable, \Stringable
{
    /**
     * @param AbstractAggregator $aggregator the aggregator function
     * @param mixed              $value      the initial value
     */
    public function __construct(protected AbstractAggregator $aggregator, mixed $value = null)
    {
        $this->addValue($value);
    }

    #[\Override]
    public function __toString(): string
    {
        $name = StringUtils::getShortName($this);
        $value = (string) $this->getValue();

        return \sprintf('%s(%s)', $name, $value);
    }

    /**
     * Adds the given value to this aggregator.
     */
    public function addValue(mixed $value): static
    {
        $this->aggregator->add($value);

        return $this;
    }

    /**
     * Gets the aggregator function.
     */
    public function getAggregator(): AbstractAggregator
    {
        return $this->aggregator;
    }

    /**
     * Gets the value.
     */
    public function getValue(): mixed
    {
        return $this->aggregator->getResult();
    }
}
