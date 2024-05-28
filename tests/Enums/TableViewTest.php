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
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableView::class)]
class TableViewTest extends TestCase
{
    use TranslatorMockTrait;

    public static function getDefault(): \Iterator
    {
        yield [TableView::getDefault(), TableView::TABLE];
        yield [PropertyServiceInterface::DEFAULT_DISPLAY_MODE, TableView::TABLE];
    }

    public static function getValues(): \Iterator
    {
        yield [TableView::TABLE, 'table'];
        yield [TableView::CUSTOM, 'custom'];
    }

    public function testCount(): void
    {
        $expected = 2;
        self::assertCount($expected, TableView::cases());
        self::assertCount($expected, TableView::sorted());
    }

    #[DataProvider('getDefault')]
    public function testDefault(TableView $value, TableView $expected): void
    {
        self::assertSame($expected, $value);
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
        $actual = TableView::sorted();
        self::assertSame($expected, $actual);
    }

    public function testTranslate(): void
    {
        $translator = $this->createTranslator();
        self::assertSame('table_view.custom', TableView::CUSTOM->trans($translator));
        self::assertSame('table_view.table', TableView::TABLE->trans($translator));
    }

    #[DataProvider('getValues')]
    public function testValue(TableView $view, string $expected): void
    {
        $actual = $view->value;
        self::assertSame($expected, $actual);
    }
}
