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

use App\Pivot\Aggregator\AbstractAggregator;
use App\Pivot\Aggregator\SumAggregator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractAggregator::class)]
#[CoversClass(SumAggregator::class)]
class SumAggregatorTest extends TestCase
{
    public function testAdd(): void
    {
        $aggregator = new SumAggregator();
        self::assertSame(0.0, $aggregator->getResult());

        $aggregator->add(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->add(new SumAggregator(10.0));
        self::assertSame(20.0, $aggregator->getResult());
    }

    public function testConstructor(): void
    {
        $aggregator = new SumAggregator();
        self::assertSame(0.0, $aggregator->getResult());

        $aggregator = new SumAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator = new SumAggregator($aggregator);
        self::assertSame(10.0, $aggregator->getResult());
    }

    public function testGetFormattedResult(): void
    {
        $aggregator = new SumAggregator(10.0);
        self::assertSame(10.0, $aggregator->getFormattedResult());

        $aggregator->add(10.00255);
        self::assertSame(20.0, $aggregator->getFormattedResult());
    }

    public function testInit(): void
    {
        $aggregator = new SumAggregator(10.0);
        self::assertSame(10.0, $aggregator->getResult());

        $aggregator->init();
        self::assertSame(0.0, $aggregator->getResult());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'name' => 'SumAggregator',
            'value' => 0.0,
        ];
        $aggregator = new SumAggregator();
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);

        $expected = [
            'name' => 'SumAggregator',
            'value' => 10.0,
        ];
        $aggregator = new SumAggregator(10);
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    public function testToString(): void
    {
        $aggregator = new SumAggregator();
        $actual = (string) $aggregator;
        self::assertSame('SumAggregator(0)', $actual);

        $aggregator = new SumAggregator(10.0);
        $actual = (string) $aggregator;
        self::assertSame('SumAggregator(10)', $actual);
    }
}
