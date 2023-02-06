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

/**
 * Unit test for the {@link TableView} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
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
        self::assertEquals($expected, $default);
        $default = PropertyServiceInterface::DEFAULT_DISPLAY_MODE;
        self::assertEquals($expected, $default);
    }

    public function testLabel(): void
    {
        self::assertEquals('table_view.custom', TableView::CUSTOM->getReadable());
        self::assertEquals('table_view.table', TableView::TABLE->getReadable());
    }

    public function testPageSize(): void
    {
        self::assertEquals(15, TableView::CUSTOM->getPageSize());
        self::assertEquals(20, TableView::TABLE->getPageSize());
    }

    public function testSorted(): void
    {
        $expected = [
            TableView::TABLE,
            TableView::CUSTOM,
        ];
        $sorted = TableView::sorted();
        self::assertEquals($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertEquals('custom', TableView::CUSTOM->value);
        self::assertEquals('table', TableView::TABLE->value);
    }
}
