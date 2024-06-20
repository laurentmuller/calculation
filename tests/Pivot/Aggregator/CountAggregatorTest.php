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
use App\Pivot\Aggregator\CountAggregator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractAggregator::class)]
#[CoversClass(CountAggregator::class)]
class CountAggregatorTest extends TestCase
{
    public function testAdd(): void
    {
        $aggregator = new CountAggregator();
        self::assertSame(0, $aggregator->getResult());

        $aggregator->add(10.0);
        self::assertSame(1, $aggregator->getResult());

        $aggregator->add(new CountAggregator(10));
        self::assertSame(2, $aggregator->getResult());
    }

    public function testConstructor(): void
    {
        $aggregator = new CountAggregator();
        self::assertSame(0, $aggregator->getResult());

        $aggregator = new CountAggregator(10);
        self::assertSame(1, $aggregator->getResult());

        $aggregator = new CountAggregator($aggregator);
        self::assertSame(1, $aggregator->getResult());
    }

    public function testGetFormattedResult(): void
    {
        $aggregator = new CountAggregator(10);
        self::assertSame(1, $aggregator->getFormattedResult());

        $aggregator->add(10);
        self::assertSame(2, $aggregator->getFormattedResult());
    }

    public function testInit(): void
    {
        $aggregator = new CountAggregator(10);
        self::assertSame(1, $aggregator->getResult());

        $aggregator->init();
        self::assertSame(0, $aggregator->getResult());
    }

    public function testJsonSerialize(): void
    {
        $expected = [
            'name' => 'CountAggregator',
            'value' => 0,
        ];
        $aggregator = new CountAggregator();
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);

        $expected = [
            'name' => 'CountAggregator',
            'value' => 1,
        ];
        $aggregator = new CountAggregator(10);
        $actual = $aggregator->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    public function testToString(): void
    {
        $aggregator = new CountAggregator();
        $actual = (string) $aggregator;
        self::assertSame('CountAggregator(0)', $actual);

        $aggregator = new CountAggregator(10.0);
        $actual = (string) $aggregator;
        self::assertSame('CountAggregator(1)', $actual);
    }
}
