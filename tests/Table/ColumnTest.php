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

namespace App\Tests\Table;

use App\Interfaces\SortModeInterface;
use App\Repository\GlobalMarginRepository;
use App\Table\Column;
use App\Table\GlobalMarginTable;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testCreateColumnAction(): void
    {
        $column = Column::createColumnAction();
        self::assertSame('id', $column->getField());
        self::assertSame('action', $column->getAlias());
        self::assertFalse($column->isSortable());
        self::assertFalse($column->isSearchable());
        self::assertSame('formatActions', $column->getCellFormatter());
        self::assertSame('actions rowlink-skip d-print-none', $column->getClass());
    }

    /**
     * @throws Exception
     */
    public function testFromJsonEmpty(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $path = __DIR__ . '/../Data/columns_empty.json';
        $table = $this->createTable();
        Column::fromJson($table, $path);
    }

    /**
     * @throws Exception
     */
    public function testFromJsonInvalidFile(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $path = __FILE__;
        $table = $this->createTable();
        Column::fromJson($table, $path);
    }

    /**
     * @throws Exception
     */
    public function testFromJsonInvalidFormatter(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $path = __DIR__ . '/../Data/columns_invalid_formatter.json';
        $table = $this->createTable();
        Column::fromJson($table, $path);
    }

    /**
     * @throws Exception
     */
    public function testFromJsonValid(): void
    {
        $table = $this->createTable();

        $path = __DIR__ . '/../Data/columns_default.json';
        $columns = Column::fromJson($table, $path);
        self::assertCount(4, $columns);
    }

    public function testGetAttribues(): void
    {
        $column = new Column();
        $column->setClass('class')
            ->setField('field')
            ->setOrder('desc')
            ->setVisible(false)
            ->setNumeric(true)
            ->setSortable(false)
            ->setSearchable(false)
            ->setDefault(true)
            ->setCellFormatter('cellFormatter')
            ->setStyleFormatter('styleFormatter');

        $actual = $column->getAttributes();
        self::assertSame('class', $actual['class']);
        self::assertSame('field', $actual['field']);
        self::assertSame('desc', $actual['sort-order']);
        self::assertFalse($actual['visible']);
        self::assertTrue($actual['numeric']);
        self::assertFalse($actual['sortable']);
        self::assertFalse($actual['searchable']);
        self::assertTrue($actual['default']);
        self::assertSame('cellFormatter', $actual['formatter']);
        self::assertSame('styleFormatter', $actual['cell-style']);

        $column->setAlias('alias');
        $actual = $column->getAttributes();
        self::assertSame('alias', $actual['field']);
    }

    public function testGetClass(): void
    {
        $column = new Column();
        $column->setClass('class')
            ->setSortable(false);
        $actual = $column->getClass();
        self::assertSame('class', $actual);

        $column->setSortable(true);
        $actual = $column->getClass();
        self::assertSame('class user-select-none cursor-pointer', $actual);
    }

    public function testGetProperties(): void
    {
        $column = new Column();
        self::assertNull($column->getFieldFormatter());
        self::assertNull($column->getStyleFormatter());
        self::assertNull($column->getTitle());
    }

    public function testInvalidSort(): void
    {
        $column = new Column();
        self::assertSame(SortModeInterface::SORT_ASC, $column->getOrder());
        $column->setOrder('fake');
        self::assertSame(SortModeInterface::SORT_ASC, $column->getOrder());
    }

    public function testMapValueBool(): void
    {
        $column = new Column();
        $column->setField('field');

        $data = [
            'id' => 1,
            'field' => true,
        ];
        $actual = $column->mapValue($data);
        self::assertSame('1', $actual);

        $data = [
            'id' => 1,
            'field' => false,
        ];
        $actual = $column->mapValue($data);
        self::assertSame('0', $actual);
        $actual = $column->mapValue($data);
        self::assertSame('0', $actual);
    }

    public function testMapValueDefault(): void
    {
        $data = [
            'id' => 1,
            'field' => 'value',
        ];
        $column = new Column();
        $column->setField('field');
        $actual = $column->mapValue($data);
        self::assertSame('value', $actual);
    }

    public function testMapValueFormatter(): void
    {
        $data = [
            'id' => 1,
            'field' => 'value',
        ];
        $callback = fn (string $value): string => 'prefix.' . $value;
        $column = new Column();
        $column->setField('field')
            ->setFieldFormatter($callback);
        $actual = $column->mapValue($data);
        self::assertSame('prefix.value', $actual);
    }

    public function testToString(): void
    {
        $column = new Column();
        $column->setField('field');
        $actual = (string) $column;
        self::assertSame('field', $actual);
    }

    /**
     * @throws Exception
     */
    private function createTable(): GlobalMarginTable
    {
        $repository = $this->createMock(GlobalMarginRepository::class);

        return new GlobalMarginTable($repository);
    }
}
