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

use App\Pivot\Aggregator\AverageAggregator;
use PHPUnit\Framework\TestCase;

class AverageAggregatorTest extends TestCase
{
    public function testAdd(): void
    {
        $aggregator = new AverageAggregator();
        self::assertSame(0.0, $aggregator->getResult());

        $aggregator->add(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->add(new AverageAggregator(10.0));
        self::assertSame(10.0, $aggregator->getResult());
    }

    public function testConstructor(): void
    {
        $aggregator = new AverageAggregator();
        self::assertSame(0.0, $aggregator->getResult());

        $aggregator = new AverageAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator = new AverageAggregator($aggregator);
        self::assertSame(10.0, $aggregator->getResult());
    }

    public function testGetFormattedResult(): void
    {
        $aggregator = new AverageAggregator(10.0);
        self::assertSame(10.0, $aggregator->getFormattedResult());

        $aggregator->add(10.00255);
        self::assertSame(10.0, $aggregator->getFormattedResult());
    }

    public function testInit(): void
    {
        $aggregator = new AverageAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->init();
        self::assertSame(0.0, $aggregator->getResult());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'name' => 'AverageAggregator',
            'value' => 0.0,
        ];
        $aggregator = new AverageAggregator();
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);

        $expected = [
            'name' => 'AverageAggregator',
            'value' => 10.0,
        ];
        $aggregator = new AverageAggregator(10);
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    public function testToString(): void
    {
        $aggregator = new AverageAggregator();
        $actual = (string) $aggregator;
        self::assertSame('AverageAggregator(0)', $actual);

        $aggregator = new AverageAggregator(10.0);
        $actual = (string) $aggregator;
        self::assertSame('AverageAggregator(10)', $actual);
    }
}
