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
use App\Tests\Fixture\FakeTable;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

final class AbstractTableTest extends TestCase
{
    public function testAllowedPageListAll(): void
    {
        $table = new FakeTable();
        $expected = TableInterface::PAGE_LIST;
        $actual = $table->getAllowedPageList(\PHP_INT_MAX);
        self::assertSame($expected, $actual);

        $expected = \array_slice(TableInterface::PAGE_LIST, 0, 3);
        $actual = $table->getAllowedPageList(11);
        self::assertSame($expected, $actual);
    }

    public function testFormats(): void
    {
        $table = new FakeTable();
        $expected = FormatUtils::formatAmount(1.0);
        $actual = $table->formatAmount(1.0);
        self::assertSame($expected, $actual);

        $date = new DatePoint();
        $expected = FormatUtils::formatDate($date);
        $actual = $table->formatDate($date);
        self::assertSame($expected, $actual);

        $expected = FormatUtils::formatId(1);
        $actual = $table->formatId(1);
        self::assertSame($expected, $actual);

        $expected = FormatUtils::formatInt(1);
        $actual = $table->formatInt(1);
        self::assertSame($expected, $actual);

        $expected = FormatUtils::formatPercent(1);
        $actual = $table->formatPercent(1);
        self::assertSame($expected, $actual);
    }

    public function testUpdateDataQueryWithColumNull(): void
    {
        $table = new FakeTable();
        $query = new DataQuery();
        $table->updateDataQuery($query);
        self::expectNotToPerformAssertions();
    }
}
