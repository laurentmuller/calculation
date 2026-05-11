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
        $this->init();
        if (null !== $value) {
            $this->add($value);
        }
    }

    #[\Override]
    public function __toString(): string
    {
        $name = StringUtils::getShortName($this);
        $value = $this->getFormattedResult();

        return \sprintf('%s(%s)', $name, $value);
    }

    /**
     * Add the given value.
     */
    abstract public function add(self|int|float|null $value): static;

    /**
     * Gets the formatted result.
     */
    public function getFormattedResult(): int|float
    {
        return $this->getResult();
    }

    /**
     * Gets the result.
     */
    abstract public function getResult(): int|float;

    /**
     * Initialize.
     */
    abstract public function init(): static;

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => StringUtils::getShortName($this),
            'value' => $this->getFormattedResult(),
        ];
    }
}
