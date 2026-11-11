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

use App\Repository\GlobalMarginRepository;
use App\Service\IndexService;
use App\Table\ColumnFactory;
use App\Table\GlobalMarginTable;
use PHPUnit\Framework\TestCase;

final class ColumnFactoryTest extends TestCase
{
    public function testCreateColumnAction(): void
    {
        $factory = new ColumnFactory();
        $column = $factory->createColumnAction();
        self::assertSame('id', $column->field);
        self::assertSame('action', $column->alias);
        self::assertFalse($column->sortable);
        self::assertFalse($column->searchable);
        self::assertSame('renderActions', $column->cellFormatter);
        self::assertSame('actions rowlink-skip d-print-none', $column->class);
    }

    public function testFromJsonEmpty(): void
    {
        $path = __DIR__ . '/../files/json/columns_empty.json';
        $message = 'The file "' . $path . '" contains no definition.';
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains($message);
        $table = $this->createTable();
        $factory = new ColumnFactory();
        $factory->load($table, $path);
    }

    public function testFromJsonInvalidFile(): void
    {
        $path = __FILE__;
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('Unable to decode value.');
        $table = $this->createTable();
        $factory = new ColumnFactory();
        $factory->load($table, $path);
    }

    public function testFromJsonInvalidFormatter(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('Unable set the property "fieldFormatter".');
        $path = __DIR__ . '/../files/json/columns_invalid_formatter.json';
        $table = $this->createTable();
        $factory = new ColumnFactory();
        $factory->load($table, $path);
    }

    public function testFromJsonValid(): void
    {
        $table = $this->createTable();
        $path = __DIR__ . '/../files/json/columns_default.json';
        $factory = new ColumnFactory();
        $columns = $factory->load($table, $path);
        self::assertCount(5, $columns);
    }

    private function createTable(): GlobalMarginTable
    {
        $catalog = \array_combine(
            \array_keys(IndexService::CATALOG),
            \array_fill(0, \count(IndexService::CATALOG), 1)
        );
        $service = self::createStub(IndexService::class);
        $service->method('getCatalog')
            ->willReturn($catalog);

        return new GlobalMarginTable(
            self::createStub(GlobalMarginRepository::class),
            $service
        );
    }
}
