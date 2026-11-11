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

use App\Entity\Product;
use App\Enums\SortMode;
use App\Table\Column;
use PHPUnit\Framework\TestCase;

final class ColumnTest extends TestCase
{
    public function testAttribues(): void
    {
        $column = Column::instance();
        $column->withClass('class')
            ->withField('field')
            ->withOrder(SortMode::DESC)
            ->withVisible(false)
            ->withNumeric(true)
            ->withSortable(false)
            ->withSearchable(false)
            ->withDefault(true)
            ->withCellFormatter('cellFormatter')
            ->withStyleFormatter('styleFormatter');

        $actual = $column->attributes();
        self::assertSame('class', $actual['class']);
        self::assertSame('field', $actual['field']);
        self::assertSame(SortMode::DESC, $actual['sort-order']);
        self::assertFalse($actual['visible']);
        self::assertTrue($actual['numeric']);
        self::assertFalse($actual['sortable']);
        self::assertFalse($actual['searchable']);
        self::assertTrue($actual['default']);
        self::assertSame('cellFormatter', $actual['formatter']);
        self::assertSame('styleFormatter', $actual['cell-style']);

        $column->withAlias('alias');
        $actual = $column->attributes();
        self::assertSame('alias', $actual['field']);
    }

    public function testDefaultProperties(): void
    {
        $column = Column::instance();
        self::assertSame('', $column->field);
        self::assertSame('', $column->alias);
        self::assertSame('user-select-none cursor-pointer', $column->class);
        self::assertSame(SortMode::DEFAULT, $column->order);

        self::assertNull($column->cellFormatter);
        self::assertNull($column->fieldFormatter);
        self::assertNull($column->styleFormatter);
        self::assertNull($column->title);

        self::assertFalse($column->default);
        self::assertFalse($column->numeric);
        self::assertTrue($column->searchable);
        self::assertTrue($column->sortable);
        self::assertTrue($column->visible);
    }

    public function testGetClass(): void
    {
        $column = Column::instance();
        $column->withClass('class')
            ->withSortable(false);
        $actual = $column->class;
        self::assertSame('class', $actual);

        $column->withSortable(true);
        $actual = $column->class;
        self::assertSame('class user-select-none cursor-pointer', $actual);
    }

    public function testMapValueBool(): void
    {
        $data = [
            'id' => 1,
            'field' => true,
        ];
        $column = Column::instance('field');
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
        $column = Column::instance('field');
        $actual = $column->mapValue($data);
        self::assertSame('value', $actual);
    }

    public function testMapValueFormatter(): void
    {
        $data = [
            'id' => 1,
            'field' => 'value',
        ];
        $callback = static fn (string $value): string => 'prefix.' . $value;
        $column = Column::instance('field')
            ->withFieldFormatter($callback);
        $actual = $column->mapValue($data);
        self::assertSame('prefix.value', $actual);
    }

    public function testMapValueObject(): void
    {
        $data = new Product()
            ->withDescription('fake');
        $column = Column::instance('description');
        $actual = $column->mapValue($data);
        self::assertSame('fake', $actual);
    }

    public function testSearchable(): void
    {
        $column = Column::instance('field');
        self::assertTrue($column->searchable);

        $column->withSearchable(false);
        self::assertFalse($column->searchable);

        $column->withSearchable(true)
            ->withVisible(false);
        self::assertFalse($column->searchable);
    }

    public function testSortable(): void
    {
        $column = Column::instance('field');
        self::assertTrue($column->sortable);

        $column->withSortable(false);
        self::assertFalse($column->sortable);

        $column->withSortable(true)
            ->withVisible(false);
        self::assertFalse($column->sortable);
    }

    public function testTitle(): void
    {
        $column = Column::instance('field');
        self::assertNull($column->title);
        $column->withTitle('title');
        self::assertSame('title', $column->title);
    }

    public function testToString(): void
    {
        $column = Column::instance('field');
        $actual = (string) $column;
        self::assertSame('field', $actual);
    }
}
