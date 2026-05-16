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

namespace App\Tests\Pivot\Aggregator;

use App\Pivot\Aggregator\MinAggregator;
use PHPUnit\Framework\TestCase;

final class MinAggregatorTest extends TestCase
{
    private float $initialValue;

    #[\Override]
    protected function setUp(): void
    {
        $this->initialValue = (float) \PHP_INT_MAX;
        parent::setUp();
    }

    public function testAdd(): void
    {
        $aggregator = new MinAggregator();
        self::assertSame($this->initialValue, $aggregator->getResult());

        $aggregator->add(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->add(new MinAggregator(0.0));
        self::assertSame(0.0, $aggregator->getResult());
    }

    public function testConstructor(): void
    {
        $aggregator = new MinAggregator();
        self::assertSame($this->initialValue, $aggregator->getResult());

        $aggregator = new MinAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator = new MinAggregator($aggregator);
        self::assertSame(10.0, $aggregator->getResult());
    }

    public function testGetFormattedResult(): void
    {
        $aggregator = new MinAggregator(10.0);
        self::assertSame(10.0, $aggregator->getFormattedResult());

        $aggregator->add(10.00255);
        self::assertSame(10.0, $aggregator->getFormattedResult());
    }

    public function testInitialize(): void
    {
        $aggregator = new MinAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->initialize();
        self::assertSame($this->initialValue, $aggregator->getResult());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'name' => 'MinAggregator',
            'value' => $this->initialValue,
        ];
        $aggregator = new MinAggregator();
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);

        $expected = [
            'name' => 'MinAggregator',
            'value' => 10.0,
        ];
        $aggregator = new MinAggregator(10);
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    public function testToString(): void
    {
        $aggregator = new MinAggregator();
        $actual = (string) $aggregator;
        $expected = \sprintf('MinAggregator(%s)', $this->initialValue);
        self::assertSame($expected, $actual);

        $aggregator = new MinAggregator(10.0);
        $actual = (string) $aggregator;
        self::assertSame('MinAggregator(10)', $actual);
    }
}
