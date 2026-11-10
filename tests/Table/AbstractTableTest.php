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

use App\Interfaces\TableInterface;
use App\Table\DataQuery;
use App\Tests\Fixture\FixtureTable;
use PHPUnit\Framework\TestCase;

final class AbstractTableTest extends TestCase
{
    public function testAllowedPageListAll(): void
    {
        $table = new FixtureTable();
        $expected = TableInterface::PAGE_LIST;
        $actual = $table->getAllowedPageList(\PHP_INT_MAX);
        self::assertSame($expected, $actual);

        $expected = \array_slice(TableInterface::PAGE_LIST, 0, 3);
        $actual = $table->getAllowedPageList(11);
        self::assertSame($expected, $actual);
    }

    public function testUpdateDataQueryWithColumNull(): void
    {
        $table = new FixtureTable();
        $query = new DataQuery();
        $table->updateDataQuery($query);
        self::assertSame('', $query->sort);
    }

    public function testValidateSortThrowException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('Invalid sorted field: "fake".');
        $table = new FixtureTable();
        $query = new DataQuery();
        $query->sort = 'fake';
        $table->updateDataQuery($query);
    }
}
