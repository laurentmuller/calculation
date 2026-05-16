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

/**
 * Abstract composite aggregator.
 */
abstract class AbstractCompositeAggregator extends AbstractAggregator
{
    /** @var non-empty-array<string, AbstractAggregator> */
    protected readonly array $aggregators;

    public function __construct(AbstractAggregator|int|float|null $value = null)
    {
        $this->aggregators = $this->createAggregators();
        parent::__construct($value);
    }

    #[\Override]
    public function add(AbstractAggregator|int|float|null $value): static
    {
        if ($value instanceof self) {
            foreach ($this->aggregators as $name => $aggregator) {
                $aggregator->add($value->aggregators[$name]);
            }
        } else {
            foreach ($this->aggregators as $aggregator) {
                $aggregator->add($value);
            }
        }

        return $this;
    }

    #[\Override]
    public function initialize(): static
    {
        foreach ($this->aggregators as $aggregator) {
            $aggregator->initialize();
        }

        return $this;
    }

    /**
     * @return non-empty-array<string, AbstractAggregator>
     */
    abstract protected function createAggregators(): array;
}
