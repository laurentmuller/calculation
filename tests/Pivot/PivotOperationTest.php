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

namespace App\Tests\Pivot;

use App\Pivot\Aggregator\AverageAggregator;
use App\Pivot\Aggregator\CountAggregator;
use App\Pivot\Aggregator\MaxAggregator;
use App\Pivot\Aggregator\MinAggregator;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\PivotOperation;
use PHPUnit\Framework\TestCase;

final class PivotOperationTest extends TestCase
{
    public function testGetAggregator(): void
    {
        self::assertSame(AverageAggregator::class, PivotOperation::AVERAGE->getAggregator());
        self::assertSame(CountAggregator::class, PivotOperation::COUNT->getAggregator());
        self::assertSame(MaxAggregator::class, PivotOperation::MAX->getAggregator());
        self::assertSame(MinAggregator::class, PivotOperation::MIN->getAggregator());
        self::assertSame(SumAggregator::class, PivotOperation::SUM->getAggregator());
    }

    public function testGetDefault(): void
    {
        self::assertSame(PivotOperation::SUM, PivotOperation::getDefault());
    }

    public function testIsInt(): void
    {
        self::assertFalse(PivotOperation::AVERAGE->isInt());
        self::assertTrue(PivotOperation::COUNT->isInt());
        self::assertFalse(PivotOperation::MAX->isInt());
        self::assertFalse(PivotOperation::MIN->isInt());
        self::assertFalse(PivotOperation::SUM->isInt());
    }

    public function testSorted(): void
    {
        $expected = [
            PivotOperation::SUM,
            PivotOperation::COUNT,
            PivotOperation::AVERAGE,
            PivotOperation::MAX,
            PivotOperation::MIN,
        ];
        self::assertSame($expected, PivotOperation::sorted());
    }
}
