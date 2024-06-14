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

use App\Enums\TableView;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Table\DataQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataQuery::class)]
class DataQueryTest extends TestCase
{
    public function testAttributes(): void
    {
        $query = new DataQuery();
        $actual = $query->attributes();
        self::assertTrue($actual['search']);
        self::assertSame('', $actual['search-text']);
        self::assertSame(0, $actual['page-size']);
        self::assertSame(1, $actual['page-number']);
        self::assertSame('', $actual['sort-name']);
        self::assertSame(SortModeInterface::SORT_ASC, $actual['sort-order']);
        self::assertFalse($actual['custom-view-default-view']);
    }

    public function testDefaultValues(): void
    {
        $actual = new DataQuery();
        self::assertFalse($actual->callback);
        self::assertSame(0, $actual->id);
        self::assertSame(TableView::TABLE, $actual->view);
        self::assertSame(0, $actual->offset);
        self::assertSame(0, $actual->limit);
        self::assertSame('', $actual->search);
        self::assertSame('', $actual->sort);
        self::assertSame(SortModeInterface::SORT_ASC, $actual->order);
        self::assertSame('', $actual->prefix);
        self::assertSame(0, $actual->groupId);
        self::assertSame(0, $actual->categoryId);
        self::assertSame(0, $actual->stateId);
        self::assertSame(0, $actual->stateEditable);
        self::assertSame('', $actual->level);
        self::assertSame('', $actual->channel);
        self::assertSame('', $actual->entity);
    }

    public function testGetPage(): void
    {
        $actual = new DataQuery();
        self::assertSame(1, $actual->getPage());

        $actual->limit = 15;
        self::assertSame(1, $actual->getPage());

        $actual->offset = 30;
        self::assertSame(3, $actual->getPage());
    }

    public function testParameters(): void
    {
        $query = new DataQuery();
        $actual = $query->parameters();
        self::assertSame(0, $actual[TableInterface::PARAM_ID]);
        self::assertSame('', $actual[TableInterface::PARAM_SEARCH]);
        self::assertSame('', $actual[TableInterface::PARAM_SORT]);
        self::assertSame(SortModeInterface::SORT_ASC, $actual[TableInterface::PARAM_ORDER]);
        self::assertSame(0, $actual[TableInterface::PARAM_OFFSET]);
        self::assertSame(TableView::TABLE->value, $actual[TableInterface::PARAM_VIEW]);
        self::assertSame(0, $actual[TableInterface::PARAM_LIMIT]);
    }
}
