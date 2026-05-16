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

use App\Pivot\Aggregator\MaxAggregator;
use PHPUnit\Framework\TestCase;

final class MaxAggregatorTest extends TestCase
{
    private float $initialValue;

    #[\Override]
    protected function setUp(): void
    {
        $this->initialValue = (float) \PHP_INT_MIN;
        parent::setUp();
    }

    public function testAdd(): void
    {
        $aggregator = new MaxAggregator();
        self::assertSame($this->initialValue, $aggregator->getResult());

        $aggregator->add(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->add(new MaxAggregator(0.0));
        self::assertSame(10.0, $aggregator->getResult());
    }

    public function testConstructor(): void
    {
        $aggregator = new MaxAggregator();
        self::assertSame($this->initialValue, $aggregator->getResult());

        $aggregator = new MaxAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator = new MaxAggregator($aggregator);
        self::assertSame(10.0, $aggregator->getResult());
    }

    public function testGetFormattedResult(): void
    {
        $aggregator = new MaxAggregator(10.0);
        self::assertSame(10.0, $aggregator->getFormattedResult());

        $aggregator->add(10.00255);
        self::assertSame(10.0, $aggregator->getFormattedResult());
    }

    public function testInitialize(): void
    {
        $aggregator = new MaxAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->initialize();
        self::assertSame($this->initialValue, $aggregator->getResult());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'name' => 'MaxAggregator',
            'value' => $this->initialValue,
        ];
        $aggregator = new MaxAggregator();
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);

        $expected = [
            'name' => 'MaxAggregator',
            'value' => 10.0,
        ];
        $aggregator = new MaxAggregator(10);
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    public function testToString(): void
    {
        $aggregator = new MaxAggregator();
        $actual = (string) $aggregator;
        self::assertSame('MaxAggregator(' . $this->initialValue . ')', $actual);

        $aggregator = new MaxAggregator(10.0);
        $actual = (string) $aggregator;
        self::assertSame('MaxAggregator(10)', $actual);
    }
}
