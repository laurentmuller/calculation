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

namespace App\Pivot;

use App\Pivot\Aggregator\AbstractAggregator;
use App\Util\Utils;

/**
 * Class with an aggregator function.
 *
 * @author Laurent Muller
 */
abstract class AbstractPivotAggregator implements \JsonSerializable, \Stringable
{
    /**
     * Constructor.
     *
     * @param AbstractAggregator $aggregator the aggregator function
     * @param mixed              $value      the initial value
     */
    public function __construct(protected AbstractAggregator $aggregator, mixed $value = null)
    {
        $this->addValue($value);
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);
        $value = (string) $this->getValue();

        return \sprintf('%s(%s)', $name, $value);
    }

    /**
     * Adds the given value to this value.
     */
    public function addValue(mixed $value): self
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
