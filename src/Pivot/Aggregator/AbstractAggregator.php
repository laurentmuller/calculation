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
 * Abstract aggregator.
 */
abstract class AbstractAggregator implements \JsonSerializable, \Stringable
{
    /**
     * @param AbstractAggregator|int|float|null $value the initial value
     */
    public function __construct(self|int|float|null $value = null)
    {
        $this->initialize();
        if (null !== $value) {
            $this->add($value);
        }
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%s(%s)',
            StringUtils::getShortName($this),
            $this->getFormattedResult()
        );
    }

    /**
     * Add the given value.
     */
    abstract public function add(self|int|float|null $value): static;

    public function getFormattedResult(): string
    {
        return (string) $this->getResult();
    }

    /**
     * Gets the result.
     */
    abstract public function getResult(): int|float;

    /**
     * Gets the rounded result.
     */
    public function getRoundResult(): int|float
    {
        return $this->getResult();
    }

    /**
     * Initialize the result.
     */
    abstract public function initialize(): static;

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => StringUtils::getShortName($this),
            'value' => $this->getRoundResult(),
        ];
    }
}
