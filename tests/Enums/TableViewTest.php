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

namespace App\Tests\Enums;

use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use Symfony\Component\Form\Test\TypeTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(TableView::class)]
class TableViewTest extends TypeTestCase
{
    public function testCount(): void
    {
        self::assertCount(2, TableView::cases());
        self::assertCount(2, TableView::sorted());
    }

    public function testDefault(): void
    {
        $expected = TableView::TABLE;
        $default = TableView::getDefault();
        self::assertSame($expected, $default);
        $default = PropertyServiceInterface::DEFAULT_DISPLAY_MODE;
        self::assertSame($expected, $default); // @phpstan-ignore-line
    }

    public function testLabel(): void
    {
        self::assertSame('table_view.custom', TableView::CUSTOM->getReadable());
        self::assertSame('table_view.table', TableView::TABLE->getReadable());
    }

    public function testPageSize(): void
    {
        self::assertSame(15, TableView::CUSTOM->getPageSize());
        self::assertSame(20, TableView::TABLE->getPageSize());
    }

    public function testSorted(): void
    {
        $expected = [
            TableView::TABLE,
            TableView::CUSTOM,
        ];
        $sorted = TableView::sorted();
        self::assertSame($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertSame('custom', TableView::CUSTOM->value); // @phpstan-ignore-line
        self::assertSame('table', TableView::TABLE->value); // @phpstan-ignore-line
    }
}
