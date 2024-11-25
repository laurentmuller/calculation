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

use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotField;
use App\Pivot\PivotCell;
use App\Pivot\PivotNode;
use App\Pivot\PivotTable;
use PHPUnit\Framework\TestCase;

class PivotTableTest extends TestCase
{
    public function testAddCell(): void
    {
        $aggregator = new SumAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');
        $table = new PivotTable(new SumAggregator());
        $cell = new PivotCell($aggregator, $column, $row);
        $table->addCell($cell);
        self::assertSame([$cell], $table->getCells());
    }

    public function testAddCellValue(): void
    {
        $aggregator = new SumAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');
        $table = new PivotTable(new SumAggregator());
        $cell = $table->addCellValue($aggregator, $column, $row);
        self::assertSame($aggregator, $cell->getAggregator());
        self::assertSame($column, $cell->getColumn());
        self::assertSame($row, $cell->getRow());
    }

    public function testConstructor(): void
    {
        $aggregator = new SumAggregator();
        $table = new PivotTable($aggregator);
        self::assertSame($aggregator, $table->getAggregator());
        self::assertNull($table->getTitle());
        self::assertNull($table->getTotalTitle());
        self::assertNull($table->getDataField());
        self::assertNull($table->getKeyField());

        self::assertSame([], $table->getCells());
        self::assertSame([], $table->getColumnFields());
        self::assertSame([], $table->getRowFields());
        self::assertNotNull($table->getRootColumn());
        self::assertNotNull($table->getRootRow());
    }

    public function testFindCellByKey(): void
    {
        $aggregator = new SumAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');
        $table = new PivotTable(new SumAggregator());
        $cell = new PivotCell($aggregator, $column, $row);
        $table->addCell($cell);
        $actual = $table->findCellByKey('fake', 'fake');
        self::assertNull($actual);
        $actual = $table->findCellByKey('column', 'row');
        self::assertNotNull($actual);
    }

    public function testFindCellByNode(): void
    {
        $aggregator = new SumAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');
        $table = new PivotTable(new SumAggregator());
        $cell = new PivotCell($aggregator, $column, $row);
        $table->addCell($cell);
        $actual = $table->findCellByNode($column, $row);
        self::assertNotNull($actual);
    }

    public function testFindCellByPath(): void
    {
        $aggregator = new SumAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');
        $table = new PivotTable(new SumAggregator());
        $cell = new PivotCell($aggregator, $column, $row);
        $table->addCell($cell);
        $actual = $table->findCellByPath('fake', 'fake');
        self::assertNull($actual);
        $actual = $table->findCellByPath('', '');
        self::assertNotNull($actual);
    }

    public function testJsonSerialize(): void
    {
        $table = new PivotTable(new SumAggregator());
        $actual = $table->jsonSerialize();
        self::assertArrayNotHasKey('title', $actual);
        self::assertArrayHasKey('aggregator', $actual);
    }

    public function testSetColumnFields(): void
    {
        $table = new PivotTable(new SumAggregator());
        $field = new PivotField('name');
        $table->setColumnFields([$field]);
        self::assertSame([$field], $table->getColumnFields());
    }

    public function testSetDataField(): void
    {
        $table = new PivotTable(new SumAggregator());
        $field = new PivotField('name');
        $table->setDataField($field);
        self::assertSame($field, $table->getDataField());
    }

    public function testSetKeyField(): void
    {
        $table = new PivotTable(new SumAggregator());
        $field = new PivotField('name');
        $table->setKeyField($field);
        self::assertSame($field, $table->getKeyField());
    }

    public function testSetRowFields(): void
    {
        $table = new PivotTable(new SumAggregator());
        $field = new PivotField('name');
        $table->setRowFields([$field]);
        self::assertSame([$field], $table->getRowFields());
    }

    public function testSetTitle(): void
    {
        $expected = 'Title';
        $table = new PivotTable(new SumAggregator());
        $table->setTitle($expected);
        self::assertSame($expected, $table->getTitle());
    }

    public function testSetTotalTitle(): void
    {
        $expected = 'Title';
        $table = new PivotTable(new SumAggregator());
        $table->setTotalTitle($expected);
        self::assertSame($expected, $table->getTotalTitle());
    }
}
