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

namespace App\Pivot\Aggregator;

use App\Utils\StringUtils;

/**
 * Abstract implementation of the aggregator interface.
 */
abstract class AbstractAggregator implements AggregatorInterface
{
    /**
     * @param AggregatorInterface|int|float|null $value the initial value
     */
    public function __construct(AggregatorInterface|int|float|null $value = null)
    {
        $this->initialize();
        $this->add($value);
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s(%s)', StringUtils::getShortName($this), $this->getFormattedResult());
    }

    #[\Override]
    public function getRoundResult(): int|float
    {
        return $this->getResult();
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => StringUtils::getShortName($this),
            'value' => $this->getRoundResult(),
        ];
    }
}
