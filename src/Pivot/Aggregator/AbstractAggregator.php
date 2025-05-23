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
 * AbstractAggregator function.
 *
 * @psalm-consistent-constructor
 */
abstract class AbstractAggregator implements \JsonSerializable, \Stringable
{
    /**
     * @param mixed|null $value the initial value
     */
    public function __construct(mixed $value = null)
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
        /** @phpstan-var mixed $value */
        $value = $this->getFormattedResult();

        return \sprintf('%s(%s)', $name, (string) $value);
    }

    /**
     * Add the given value.
     *
     * @param mixed $value the value to add
     */
    abstract public function add(mixed $value): static;

    /**
     * Gets the formatted result.
     */
    public function getFormattedResult(): mixed
    {
        return $this->getResult();
    }

    /**
     * Gets the result.
     */
    abstract public function getResult(): mixed;

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
