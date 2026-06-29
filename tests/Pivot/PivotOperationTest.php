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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PivotOperationTest extends TestCase
{
    public static function getAggregators(): \Generator
    {
        yield [PivotOperation::AVERAGE, AverageAggregator::class];
        yield [PivotOperation::COUNT, CountAggregator::class];
        yield [PivotOperation::MAX, MaxAggregator::class];
        yield [PivotOperation::MIN, MinAggregator::class];
        yield [PivotOperation::SUM, SumAggregator::class];
    }

    public static function getIsInts(): \Generator
    {
        yield [PivotOperation::AVERAGE, false];
        yield [PivotOperation::COUNT, true];
        yield [PivotOperation::MAX, false];
        yield [PivotOperation::MIN, false];
        yield [PivotOperation::SUM, false];
    }

    /**
     * @param class-string $expected
     */
    #[DataProvider('getAggregators')]
    public function testCreateAggregator(PivotOperation $operation, string $expected): void
    {
        self::assertInstanceOf($expected, $operation->createAggregator());
    }

    #[DataProvider('getAggregators')]
    public function testGetAggregator(PivotOperation $operation, string $expected): void
    {
        self::assertSame($expected, $operation->getAggregator());
    }

    public function testGetDefault(): void
    {
        self::assertSame(PivotOperation::SUM, PivotOperation::DEFAULT);
    }

    #[DataProvider('getIsInts')]
    public function testIsInt(PivotOperation $operation, bool $expected): void
    {
        self::assertSame($expected, $operation->isInt());
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
