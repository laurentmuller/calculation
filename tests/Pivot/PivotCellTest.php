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

class PivotCellTest extends TestCase
{
    public function testConstructor(): void
    {
        $aggregator = new CountAggregator();
        $node = $this->createNode($aggregator);
        self::assertSame($aggregator, $node->getAggregator());
        self::assertSame($aggregator, $node->getColumn()->getAggregator());
        self::assertSame($aggregator, $node->getRow()->getAggregator());
    }

    public function testEqualsKey(): void
    {
        $node = $this->createNode();
        self::assertTrue($node->equalsKey('column', 'row'));
        self::assertFalse($node->equalsKey('fake', 'fake'));
    }

    public function testEqualsNode(): void
    {
        $node = $this->createNode();

        $aggregator = new CountAggregator();
        $column = new PivotNode($aggregator);
        $row = new PivotNode($aggregator);
        self::assertFalse($node->equalsNode($column, $row));

        $column = $node->getColumn();
        $row = $node->getRow();
        self::assertTrue($node->equalsNode($column, $row));
    }

    public function testEqualsPath(): void
    {
        $node = $this->createNode();
        self::assertTrue($node->equalsPath('', ''));
        self::assertFalse($node->equalsPath('fake', 'fake'));
    }

    public function testGetColumnPath(): void
    {
        $node = $this->createNode();
        self::assertSame('', $node->getColumnPath());
    }

    public function testGetColumnTitle(): void
    {
        $node = $this->createNode();
        self::assertSame('', $node->getColumnTitle());
    }

    public function testGetFormattedResult(): void
    {
        $node = $this->createNode();
        self::assertSame(0, $node->getFormattedResult());
    }

    public function testGetResult(): void
    {
        $node = $this->createNode();
        self::assertSame(0, $node->getResult());
    }

    public function testGetRowPath(): void
    {
        $node = $this->createNode();
        self::assertSame('', $node->getRowPath());
    }

    public function testGetRowTitle(): void
    {
        $node = $this->createNode();
        self::assertSame('', $node->getRowTitle());
    }

    public function testJsonSerialize(): void
    {
        $node = $this->createNode();
        $actual = $node->jsonSerialize();
        self::assertCount(3, $actual);
        self::assertArrayHasKey('row', $actual);
        self::assertArrayHasKey('col', $actual);
        self::assertArrayHasKey('value', $actual);
    }

    public function testToString(): void
    {
        $node = $this->createNode();
        $actual = (string) $node;
        self::assertSame('PivotCell(0)', $actual);
    }

    private function createNode(?AbstractAggregator $aggregator = null): PivotCell
    {
        $aggregator ??= new CountAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');

        return new PivotCell($aggregator, $column, $row);
    }
}
