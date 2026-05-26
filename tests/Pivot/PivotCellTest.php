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

use App\Pivot\Aggregator\AbstractAggregator;
use App\Pivot\Aggregator\CountAggregator;
use App\Pivot\PivotCell;
use App\Pivot\PivotNode;
use PHPUnit\Framework\TestCase;

final class PivotCellTest extends TestCase
{
    public function testConstructor(): void
    {
        $aggregator = new CountAggregator();
        $cell = $this->createCell($aggregator);
        self::assertSame($aggregator, $cell->getAggregator());
        self::assertSame($aggregator, $cell->getColumn()->getAggregator());
        self::assertSame($aggregator, $cell->getRow()->getAggregator());
    }

    public function testEqualsKey(): void
    {
        $cell = $this->createCell();
        self::assertTrue($cell->equalsKey('column', 'row'));
        self::assertFalse($cell->equalsKey('fake', 'fake'));
    }

    public function testEqualsNode(): void
    {
        $cell = $this->createCell();

        $aggregator = new CountAggregator();
        $column = new PivotNode($aggregator);
        $row = new PivotNode($aggregator);
        self::assertFalse($cell->equalsNode($column, $row));

        $column = $cell->getColumn();
        $row = $cell->getRow();
        self::assertTrue($cell->equalsNode($column, $row));
    }

    public function testEqualsPath(): void
    {
        $cell = $this->createCell();
        self::assertTrue($cell->equalsPath('', ''));
        self::assertFalse($cell->equalsPath('fake', 'fake'));
    }

    public function testGetColumnPath(): void
    {
        $cell = $this->createCell();
        self::assertSame('', $cell->getColumnPath());
    }

    public function testGetColumnTitle(): void
    {
        $node = $this->createCell();
        self::assertSame('', $node->getColumnTitle());
    }

    public function testGetResult(): void
    {
        $cell = $this->createCell();
        self::assertSame(0, $cell->getResult());
    }

    public function testGetRoundResult(): void
    {
        $cell = $this->createCell();
        self::assertSame(0, $cell->getRoundResult());
    }

    public function testGetRowPath(): void
    {
        $cell = $this->createCell();
        self::assertSame('', $cell->getRowPath());
    }

    public function testGetRowTitle(): void
    {
        $cell = $this->createCell();
        self::assertSame('', $cell->getRowTitle());
    }

    public function testJsonSerialize(): void
    {
        $cell = $this->createCell();
        $actual = $cell->jsonSerialize();
        self::assertCount(3, $actual);
        self::assertArrayHasKey('row', $actual);
        self::assertArrayHasKey('column', $actual);
        self::assertArrayHasKey('value', $actual);
    }

    public function testToString(): void
    {
        $cell = $this->createCell();
        $actual = (string) $cell;
        self::assertSame('PivotCell(0)', $actual);
    }

    private function createCell(?AbstractAggregator $aggregator = null): PivotCell
    {
        $aggregator ??= new CountAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');

        return new PivotCell($aggregator, $column, $row);
    }
}
