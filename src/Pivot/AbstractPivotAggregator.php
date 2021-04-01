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
abstract class AbstractPivotAggregator implements \JsonSerializable
{
    /**
     * The aggregator function.
     *
     * @var AbstractAggregator
     */
    protected $aggregator;

    /**
     * Constructor.
     *
     * @param AbstractAggregator $aggregator the aggregator function
     * @param mixed              $value      the initial value
     */
    public function __construct(AbstractAggregator $aggregator, $value = null)
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
    public function getAggregator(): AbstractAggregator
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