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

use App\Spreadsheet\HeaderFooter;
use App\Spreadsheet\SpreadsheetDocument;
use App\Spreadsheet\WorksheetDocument;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;

class HeaderFooterTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAddCenter(): void
    {
        $header = HeaderFooter::header();
        $header->addCenter('Content');
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&C&9&"-,Regular"Content', $actual);
    }

    public function testAddDateCenter(): void
    {
        $header = HeaderFooter::header();
        $header->addDateTime(HeaderFooter::CENTER_SECTION);
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&C&9&"-,Regular"&D - &T', $actual);
    }

    public function testAddDateDefault(): void
    {
        $header = HeaderFooter::header();
        $header->addDateTime();
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&R&9&"-,Regular"&D - &T', $actual);
    }

    public function testAddDateLeft(): void
    {
        $header = HeaderFooter::header();
        $header->addDateTime(HeaderFooter::LEFT_SECTION);
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&L&9&"-,Regular"&D - &T', $actual);
    }

    public function testAddLeft(): void
    {
        $header = HeaderFooter::header();
        $header->addLeft('Content');
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&L&9&"-,Regular"Content', $actual);
    }

    public function testAddLeftBold(): void
    {
        $header = HeaderFooter::header();
        $header->addLeft('Content', true);
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&L&9&BContent', $actual);
    }

    public function testAddLeftEmpty(): void
    {
        $header = HeaderFooter::header();
        $header->addLeft('');
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('', $actual);
    }

    public function testAddLeftMultiLine(): void
    {
        $header = HeaderFooter::header();
        $header->addLeft('First');
        $header->addLeft('Second');
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        $expected = "&L&9&\"-,Regular\"First\n&9&\"-,Regular\"Second";
        self::assertSame($expected, $actual);
    }

    public function testAddLeftNull(): void
    {
        $header = HeaderFooter::header();
        $header->addLeft(null);
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('', $actual);
    }

    public function testAddPagesCenter(): void
    {
        $header = HeaderFooter::header();
        $header->addPages(HeaderFooter::CENTER_SECTION);
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&C&9&"-,Regular"Page &P / &N', $actual);
    }

    public function testAddPagesDefault(): void
    {
        $header = HeaderFooter::header();
        $header->addPages();
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&L&9&"-,Regular"Page &P / &N', $actual);
    }

    public function testAddPagesRight(): void
    {
        $header = HeaderFooter::header();
        $header->addPages(HeaderFooter::RIGHT_SECTION);
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&R&9&"-,Regular"Page &P / &N', $actual);
    }

    public function testAddRight(): void
    {
        $header = HeaderFooter::header();
        $header->addRight('Content');
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('&R&9&"-,Regular"Content', $actual);
    }

    public function testEmptyFooter(): void
    {
        $header = HeaderFooter::footer();
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddFooter();
        self::assertSame('', $actual);
    }

    public function testEmptyHeader(): void
    {
        $header = HeaderFooter::header();
        $sheet = $this->createWorksheet();
        $header->apply($sheet);
        $actual = $sheet->getHeaderFooter()
            ->getOddHeader();
        self::assertSame('', $actual);
    }

    private function createWorksheet(): WorksheetDocument
    {
        $document = new SpreadsheetDocument($this->createMockTranslator());

        return $document->getActiveSheet();
    }
}
