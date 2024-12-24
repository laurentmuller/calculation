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

namespace App\Tests\Spreadsheet;

use App\Controller\AbstractController;
use App\Model\CustomerInformation;
use App\Spreadsheet\HeaderFormat;
use App\Spreadsheet\SpreadsheetDocument;
use App\Spreadsheet\WorksheetDocument;
use App\Tests\TranslatorMockTrait;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PHPUnit\Framework\TestCase;

class WorksheetDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    public function testConstructor(): void
    {
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $sheet = new WorksheetDocument($doc);
        self::assertSame($doc, $sheet->getParent());
    }

    public function testFinish(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->finish();
        self::assertSame('A2', $sheet->getActiveCell());
    }

    public function testHeaderFooter(): void
    {
        $cs = new CustomerInformation();
        $cs->setPrintAddress(true)
            ->setAddress('Address')
            ->setEmail('Email')
            ->setName('Name')
            ->setPhone('Phone')
            ->setUrl('URL')
            ->setZipCity('ZipCity');

        $sheet = $this->getActiveSheet();
        $sheet->updateHeaderFooter($cs);
        $actual = $sheet->getPageMargins()
            ->getTop();
        self::assertSame(0.83, $actual);
        $actual = $sheet->getPageMargins()
            ->getBottom();
        self::assertSame(0.47, $actual);
    }

    public function testRebindParentException(): void
    {
        self::expectException(Exception::class);
        $doc1 = new SpreadsheetDocument($this->createMockTranslator());
        $doc2 = new Spreadsheet();
        $sheet = $doc1->addSheet(new WorksheetDocument(title: 'Fake'));
        $sheet->rebindParent($doc2);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSetActiveTitle(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $doc->setActiveTitle('My Title', $controller);
        $sheet = $doc->getActiveSheet();
        self::assertSame('My Title', $sheet->getTitle());
    }

    public function testSetCellImage(): void
    {
        $path = __DIR__ . '/../files/images/example.png';
        $sheet = $this->getActiveSheet();
        $sheet->setCellImage($path, 'A1', 124, 147);
        $actual = $sheet->getColumnDimension('A')->getWidth();
        self::assertSame(124.0, $actual);
        $actual = $sheet->getRowDimension(1)->getRowHeight();
        self::assertSame(147.0, $actual);
    }

    public function testSetCellLink(): void
    {
        $expected = 'https://www.example.com';
        $sheet = $this->getActiveSheet();
        $sheet->setCellLink(1, 1, $expected, underline: true);
        $cell = $sheet->getCell('A1');
        $actual = $cell->getHyperlink()->getUrl();
        self::assertSame($expected, $actual);
    }

    public function testSetCellLinkEmpty(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setCellLink(1, 1, '');
        $cell = $sheet->getCell('A1');
        $actual = $cell->getHyperlink()->getUrl();
        self::assertSame('', $actual);
    }

    public function testSetCellLinkNoColor(): void
    {
        $expected = 'https://www.example.com';
        $sheet = $this->getActiveSheet();
        $sheet->setCellLink(1, 1, $expected, '');
        $cell = $sheet->getCell('A1');
        $actual = $cell->getHyperlink()->getUrl();
        self::assertSame($expected, $actual);
    }

    public function testSetColumnConditional(): void
    {
        $sheet = $this->getActiveSheet();
        $conditional = new Conditional();
        $sheet->setColumnConditional(1, $conditional);
        $actual = $sheet->getStyle('A')
            ->getConditionalStyles();
        self::assertCount(1, $actual);
    }

    public function testSetColumnWidth(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setColumnWidth(1, 100);
        $actual = $sheet->getColumnDimensionByColumn(1)->getWidth();
        self::assertSame(100.0, $actual);
    }

    public function testSetForeground(): void
    {
        $sheet = $this->getActiveSheet();

        $expected = 'FF0000FF';
        $sheet->setCellValue('A1', 'fake');
        $sheet->setForeground(1, Color::COLOR_BLUE, true);
        $actual = $sheet->getStyle('A')->getFont()
            ->getColor()
            ->getARGB();
        self::assertSame($expected, $actual);

        $expected = '000000';
        $sheet->setCellValue('B1', 'fake');
        $sheet->setForeground(1, 'FF00FF', true);
        $actual = $sheet->getStyle('B')->getFont()
            ->getColor()
            ->getRGB();
        self::assertSame($expected, $actual);

        $expected = 'FF000000';
        $sheet->setCellValue('C1', 'fake');
        $sheet->setForeground(1, $expected);
        $actual = $sheet->getStyle('C')->getFont()
            ->getColor()
            ->getARGB();
        self::assertSame($expected, $actual);
    }

    public function testSetFormat(): void
    {
        $expected = NumberFormat::FORMAT_NUMBER_00;
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormat(1, $expected);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetFormatAmount(): void
    {
        $format = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatAmount(1);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($format, $actual);

        $expected = "[Red][<=0]$format;$format";
        $sheet->setFormatAmount(1, true);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetFormatBoolean(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatBoolean(1, 'true', 'false', true);
        $sheet->setFormatBoolean(2, 'true', 'false', true);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame('"true";;"false";@', $actual);
    }

    public function testSetFormatDate(): void
    {
        $expected = NumberFormat::FORMAT_DATE_DDMMYYYY;
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatDate(1);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetFormatDateTime(): void
    {
        $expected = 'dd/mm/yyyy hh:mm';
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatDateTime(1);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetFormatId(): void
    {
        $expected = '000000';
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatId(1);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetFormatInt(): void
    {
        $expected = '#,##0';
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatInt(1);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetFormatPercent(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');

        $expected = NumberFormat::FORMAT_PERCENTAGE;
        $sheet->setFormatPercent(1);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);

        $expected = NumberFormat::FORMAT_PERCENTAGE_00;
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatPercent(1, true);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetFormatYesNo(): void
    {
        $expected = '"common.value_true";;"common.value_false";@';
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setFormatYesNo(1);
        $actual = $sheet->getStyle('A')
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame($expected, $actual);
    }

    public function testSetHeaders(): void
    {
        $sheet = $this->getActiveSheet();
        $actual = $sheet->setHeaders([]);
        self::assertSame(1, $actual);

        $format = new HeaderFormat();
        $actual = $sheet->setHeaders(['key' => $format]);
        self::assertSame(2, $actual);
    }

    public function testSetPageLandscape(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setPageLandscape();
        $actual = $sheet->getPageSetup()->getOrientation();
        self::assertSame(PageSetup::ORIENTATION_LANDSCAPE, $actual);
    }

    public function testSetRowValues(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setRowValues(1, [1, 2, 3]);
        self::assertSame(1, $sheet->getCell('A1')->getValue());
        self::assertSame(2, $sheet->getCell('B1')->getValue());
        self::assertSame(3, $sheet->getCell('C1')->getValue());
    }

    public function testSetTitleToLong(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setTitle(\str_repeat('A', 40));
        $actual = $sheet->getTitle();
        self::assertSame(\str_repeat('A', 31), $actual);
    }

    public function testSetWrapText(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');
        $sheet->setWrapText(1);
        $actual = $sheet->getColumnDimension('A')
            ->getAutoSize();
        self::assertFalse($actual);
        $actual = $sheet->getStyle('A1')
            ->getAlignment()
            ->getWrapText();
        self::assertTrue($actual);
    }

    public function testWorksheetDocument(): void
    {
        $sheet = $this->getActiveSheet();
        $sheet->mergeContent(1, 10, 1, 1);
        $sheet->setAutoSize(1);
        $sheet->setCellContent(1, 1, null);
        $sheet->setCellContent(1, 1, '');
        $sheet->setCellContent(2, 1, true);
        $sheet->setCellContent(3, 1, new \DateTime());
        $sheet->setCellContent(4, 1, 'Fake');

        $actual = $sheet->getColumnStyle(0);
        self::assertInstanceOf(Style::class, $actual);
        self::assertInstanceOf(SpreadsheetDocument::class, $sheet->getParent());
        $actual = $sheet->getPercentFormat();
        self::assertSame(NumberFormat::FORMAT_PERCENTAGE, $actual);
        $actual = $sheet->getPercentFormat(true);
        self::assertSame(NumberFormat::FORMAT_PERCENTAGE_00, $actual);
    }

    private function getActiveSheet(): WorksheetDocument
    {
        $doc = new SpreadsheetDocument($this->createMockTranslator());

        return $doc->getActiveSheet();
    }
}
