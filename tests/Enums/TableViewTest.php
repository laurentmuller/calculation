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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(TableView::class)]
class TableViewTest extends TestCase
{
    public static function getDefault(): array
    {
        return [
            [TableView::getDefault(), TableView::TABLE],
            [PropertyServiceInterface::DEFAULT_DISPLAY_MODE, TableView::TABLE],
        ];
    }

    public static function getValues(): array
    {
        return [
            [TableView::TABLE, 'table'],
            [TableView::CUSTOM, 'custom'],
        ];
    }

    public function testCount(): void
    {
        $expected = 2;
        self::assertCount($expected, TableView::cases());
        self::assertCount($expected, TableView::sorted());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDefault')]
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

    /**
     * @throws Exception
     */
    public function testTranslate(): void
    {
        $translator = $this->createTranslator();
        self::assertSame('table_view.custom', TableView::CUSTOM->trans($translator));
        self::assertSame('table_view.table', TableView::TABLE->trans($translator));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(TableView $view, string $expected): void
    {
        $actual = $view->value;
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }
}
