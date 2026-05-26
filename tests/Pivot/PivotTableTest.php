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
use App\Pivot\PivotOperation;
use App\Pivot\PivotTable;
use PHPUnit\Framework\TestCase;

final class PivotTableTest extends TestCase
{
    public function testAddCell(): void
    {
        $table = $this->createPivotTable();
        $cell = $this->createPivotCell();
        $table->addCell($cell);
        self::assertSame([$cell], $table->getCells());
    }

    public function testAddCellValue(): void
    {
        $aggregator = new SumAggregator();
        $column = new PivotNode($aggregator, 'column');
        $row = new PivotNode($aggregator, 'row');
        $table = $this->createPivotTable();
        $cell = $table->addCellValue($aggregator, $column, $row);
        self::assertSame($aggregator, $cell->getAggregator());
        self::assertSame($column, $cell->getColumn());
        self::assertSame($row, $cell->getRow());
    }

    public function testConstructor(): void
    {
        $table = $this->createPivotTable();
        self::assertInstanceOf(SumAggregator::class, $table->getAggregator());
        self::assertNull($table->getTitle());
        self::assertNull($table->getTotalTitle());
        self::assertNull($table->getDataField());

        self::assertSame([], $table->getCells());
        self::assertSame([], $table->getColumnFields());
        self::assertSame([], $table->getRowFields());
        self::assertNotNull($table->getRootColumn());
        self::assertNotNull($table->getRootRow());

        self::assertSame(0, $table->getMaxColumnLevel());
        self::assertSame(0, $table->getMaxRowLevel());
    }

    public function testFindCellByKey(): void
    {
        $table = $this->createPivotTable();
        $cell = $this->createPivotCell();
        $table->addCell($cell);
        $actual = $table->findCellByKey('fake', 'fake');
        self::assertNull($actual);
        $actual = $table->findCellByKey('column', 'row');
        self::assertNotNull($actual);
    }

    public function testFindCellByNode(): void
    {
        $table = $this->createPivotTable();
        $cell = $this->createPivotCell();
        $table->addCell($cell);

        $column = $cell->getColumn();
        $row = $cell->getRow();
        $actual = $table->findCellByNode($column, $row);
        self::assertSame($cell, $actual);
    }

    public function testFindCellByPath(): void
    {
        $table = $this->createPivotTable();
        $cell = $this->createPivotCell();
        $table->addCell($cell);
        $actual = $table->findCellByPath('fake', 'fake');
        self::assertNull($actual);
        $actual = $table->findCellByPath('', '');
        self::assertSame($cell, $actual);
    }

    public function testJsonSerialize(): void
    {
        $table = $this->createPivotTable();
        $table->setTitle('Title');
        $cell = $this->createPivotCell();
        $table->addCell($cell);

        $actual = $table->jsonSerialize();
        self::assertArrayHasKey('title', $actual);
        self::assertArrayHasKey('aggregator', $actual);

        self::assertArrayNotHasKey('value', $actual);
        self::assertArrayNotHasKey('dataField', $actual);
        self::assertArrayNotHasKey('columnFields', $actual);
        self::assertArrayNotHasKey('rowFields', $actual);

        self::assertArrayHasKey('rootColumn', $actual);
        self::assertArrayHasKey('rootRow', $actual);
        self::assertArrayHasKey('cells', $actual);
    }

    public function testSetColumnFields(): void
    {
        $table = $this->createPivotTable();
        $field = new PivotField('name');
        $table->setColumnFields([$field]);
        self::assertSame([$field], $table->getColumnFields());
    }

    public function testSetDataField(): void
    {
        $table = $this->createPivotTable();
        $field = new PivotField('name');
        $table->setDataField($field);
        self::assertSame($field, $table->getDataField());
    }

    public function testSetRowFields(): void
    {
        $table = $this->createPivotTable();
        $field = new PivotField('name');
        $table->setRowFields([$field]);
        self::assertSame([$field], $table->getRowFields());
    }

    public function testSetTitle(): void
    {
        $expected = 'Title';
        $table = $this->createPivotTable();
        $table->setTitle($expected);
        self::assertSame($expected, $table->getTitle());
    }

    public function testSetTotalTitle(): void
    {
        $expected = 'Title';
        $table = $this->createPivotTable();
        $table->setTotalTitle($expected);
        self::assertSame($expected, $table->getTotalTitle());
    }

    private function createPivotCell(): PivotCell
    {
        $column = new PivotNode(new SumAggregator(), 'column');
        $row = new PivotNode(new SumAggregator(), 'row');

        return new PivotCell(new SumAggregator(), $column, $row);
    }

    private function createPivotTable(): PivotTable
    {
        return new PivotTable(PivotOperation::SUM);
    }
}
