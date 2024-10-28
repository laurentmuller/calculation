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

namespace App\Tests\Pdf;

use App\Pdf\Events\PdfCellBackgroundEvent;
use App\Pdf\Events\PdfCellBorderEvent;
use App\Pdf\Events\PdfCellTextEvent;
use App\Pdf\Events\PdfPdfDrawHeadersEvent;
use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\Interfaces\PdfDrawCellBorderInterface;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\Interfaces\PdfDrawHeadersInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfImageCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use fpdf\PdfDocument;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;

class PdfTableTest extends TestCase
{
    public function testAddCellNone(): void
    {
        $table = $this->createTable()
            ->startRow()
            ->add();
        self::assertSame(0, $table->getColumnsCount());
    }

    public function testAddCellNoRowStarted(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('No row started.');
        $this->createTable()
            ->addCell(new PdfCell());
    }

    public function testAddCellsNoRowStarted(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('No row started.');
        $this->createTable()
            ->addValues(new PdfCell());
    }

    public function testAddColumns(): void
    {
        $table = $this->createTable();
        $column = new PdfColumn();
        $table->addColumns($column);
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testAddHeaderRow(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $table->addColumn(new PdfColumn())
            ->addHeaderRow('Test');
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testAddRow(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $table->addColumn(new PdfColumn())
            ->addRow('Test');
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testAddStyledRow(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $table->addColumn(new PdfColumn())
            ->addStyledRow(['Test']);
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testAdjustCellWidths(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $table->addColumns(
            PdfColumn::left('left', 25.0, true),
            PdfColumn::left('left', 25.0),
            PdfColumn::right('left', 25.0)
        );
        $table->addRow('left', 'left', 'right');
        $table->startRow()
            ->add('left', 2)
            ->add('right')
            ->endRow();

        self::assertSame(3, $table->getColumnsCount());
    }

    public function testAlignment(): void
    {
        $table = $this->createTable(false)
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $table->setAlignment(PdfTextAlignment::CENTER);
        $table->singleLine('text');
        $table->setAlignment(PdfTextAlignment::RIGHT);
        $table->singleLine('text');
        $table->setAlignment(PdfTextAlignment::LEFT);
        $table->singleLine('text');
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testBackgroundListener(): void
    {
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $listener = new class() implements PdfDrawCellBackgroundInterface {
            public function drawCellBackground(PdfCellBackgroundEvent $event): bool
            {
                TestCase::assertNotNull($event->getDocument());

                return true;
            }
        };
        $table->setBackgroundListener($listener);
        $table->outputHeaders();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testBorderListener(): void
    {
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $listener = new class() implements PdfDrawCellBorderInterface {
            public function drawCellBorder(PdfCellBorderEvent $event): bool
            {
                TestCase::assertNotNull($event->getDocument());

                return true;
            }
        };
        $table->setBorderListener($listener);
        $table->outputHeaders();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testCellBorder(): void
    {
        $table = $this->createTable()
            ->addColumns(
                new PdfColumn(),
                new PdfColumn(),
                new PdfColumn(),
                new PdfColumn()
            );

        $table->getParent()->addPage();
        $cell1 = new PdfCell(style: PdfStyle::getCellStyle()->setBorder(PdfBorder::all()));
        $cell2 = new PdfCell(style: PdfStyle::getCellStyle()->setBorder(PdfBorder::none()));
        $cell3 = new PdfCell(style: PdfStyle::getCellStyle()->setBorder(PdfBorder::leftRight()));
        $cell4 = new PdfCell(style: PdfStyle::getCellStyle()->setBorder(PdfBorder::topBottom()));
        $table->startRow()
            ->addValues($cell1, $cell2, $cell3, $cell4)
            ->endRow();
        self::assertSame(4, $table->getColumnsCount());
    }

    public function testCellFontSizeAndIndent(): void
    {
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $style = PdfStyle::getCellStyle()
            ->setFontSize(12.0)
            ->setIndent(3.0);
        $table->singleLine('text', $style);
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testCellImage(): void
    {
        $path = __DIR__ . '/../Data/images/example.png';
        if (!\file_exists($path)) {
            self::fail('Unable to find image.');
        }
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $cell = new PdfImageCell($path);
        $table->startRow()
            ->addCell($cell)
            ->endRow();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testCellLink(): void
    {
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $cell = new PdfCell(link: 'https://example.com');
        $table->startRow()
            ->addCell($cell)
            ->endRow();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testCheckNewPage(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $table->addColumn(new PdfColumn());
        $table->checkNewPage(10_000);
        self::assertSame(2, $table->getParent()->getPage());
    }

    public function testCompleteRow(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $table->addColumns(new PdfColumn(), new PdfColumn());
        $table->startRow()
            ->completeRow();
        self::assertSame(2, $table->getColumnsCount());
    }

    public function testCompleteRowNoRowStarted(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('No row started.');
        $this->createTable()
            ->completeRow();
    }

    public function testEndRowCellSpan(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('Invalid spanned cells: expected 1, 2 given.');
        $this->createTable()
            ->addColumn(PdfColumn::left(width: 25.0))
            ->startRow()
            ->addCell(new PdfCell(cols: 2))
            ->endRow();
    }

    public function testEndRowNoCell(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('No cell defined.');
        $this->createTable()
            ->endRow();
    }

    public function testGetBorder(): void
    {
        $table = $this->createTable();
        $actual = $table->getBorder();
        self::assertTrue($actual->isAll());
    }

    public function testGetColumns(): void
    {
        $column = new PdfColumn();
        $table = $this->createTable()
            ->addColumn($column);
        self::assertSame([$column], $table->getColumns());
    }

    public function testHeaders(): void
    {
        $table = $this->createTable();
        $table->setAlignment(PdfTextAlignment::CENTER)
            ->setRepeatHeader(false)
            ->setHeaderStyle();
        self::assertFalse($table->isHeaders());
        self::assertFalse($table->isRowStarted());
    }

    public function testHeadersListener(): void
    {
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $listener = new class() implements PdfDrawHeadersInterface {
            public function drawHeaders(PdfPdfDrawHeadersEvent $event): bool
            {
                $columns = $event->getColumns();
                TestCase::assertCount(1, $columns);
                TestCase::assertNotNull($event->getDocument());

                return true;
            }
        };
        $table->setHeadersListener($listener);
        $table->outputHeaders();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testImageCellInvalid(): void
    {
        self::expectException(PdfException::class);
        self::expectExceptionMessage("The image 'fake' does not exist.");
        new PdfImageCell('fake');
    }

    public function testImageCellValid(): void
    {
        $path = __DIR__ . '/../Data/images/example.png';

        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $cell = new PdfImageCell($path);
        self::assertSame($path, $cell->getPath());

        $expected = \getimagesize($path);
        self::assertIsArray($expected);
        self::assertSame($expected[0], $cell->getWidth());
        self::assertSame($expected[1], $cell->getHeight());

        $cell->resize();
        $cell->resize(height: 248);
        $cell->resize(width: 294);

        $actual = $cell->getOriginalSize();
        self::assertSame($expected[0], $actual[0]);
        self::assertSame($expected[1], $actual[1]);

        $table->getParent()->addPage();
        $table->startRow()
            ->addCell($cell)
            ->endRow();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testImageCellWithLongText(): void
    {
        $path = __DIR__ . '/../Data/images/example.png';
        $column = PdfColumn::left(width: 25.0, fixed: true);
        $table = $this->createTable(false)
            ->addColumn($column);
        $table->getParent()
            ->addPage();
        $cell = new PdfImageCell($path, 'A very long text used to check for trim.');
        $table->startRow()
            ->addCell($cell)
            ->endRow();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testIsFullWidth(): void
    {
        $table = $this->createTable();
        self::assertTrue($table->isFullWidth());
    }

    public function testListeners(): void
    {
        $table = $this->createTable();
        $table->setBackgroundListener(null)
            ->setBorderListener(null)
            ->setHeadersListener(null)
            ->setTextListener(null);
        self::assertSame(0, $table->getColumnsCount());
    }

    public function testOutputHeadersNoColumn(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('No column defined.');
        $this->createTable()
            ->outputHeaders();
    }

    public function testSetBorder(): void
    {
        $table = $this->createTable();
        $expected = PdfBorder::leftRight();
        $table->setBorder($expected);
        self::assertSame($expected, $table->getBorder());
    }

    public function testSetCellStyle(): void
    {
        $expected = PdfStyle::getNoBorderStyle();
        $table = $this->createTable()
            ->setCellStyle($expected);
        $actual = $table->getCellStyle();
        self::assertSame($expected, $actual);
    }

    public function testSingleLine(): void
    {
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $table->singleLine();
        self::assertSame(1, $table->getColumnsCount());
    }

    public function testStartHeaderRowAlreadyStarted(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('Row already started.');
        $this->createTable()
            ->startHeaderRow()
            ->startHeaderRow();
    }

    public function testStartRowAlreadyStarted(): void
    {
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage('Row already started.');
        $this->createTable()
            ->startRow()
            ->startRow();
    }

    public function testTextListener(): void
    {
        $table = $this->createTable()
            ->addColumn(new PdfColumn());
        $table->getParent()->addPage();
        $listener = new class() implements PdfDrawCellTextInterface {
            public function drawCellText(PdfCellTextEvent $event): bool
            {
                TestCase::assertNotNull($event->getDocument());

                return true;
            }
        };
        $table->setTextListener($listener);
        $table->outputHeaders();
        self::assertSame(1, $table->getColumnsCount());
    }

    private function createTable(bool $fullWidth = true): PdfTable
    {
        $document = new PdfDocument();

        return PdfTable::instance($document, $fullWidth);
    }
}
