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

use App\Pdf\PdfFont;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfFontStyle;
use fpdf\PdfDocument;
use PHPUnit\Framework\TestCase;

class PdfFontTest extends TestCase
{
    public function testApply(): void
    {
        $document = new PdfDocument();
        $actual = new PdfFont();
        $actual->apply($document);
        self::assertSame(0, $document->getPage());
    }

    public function testBold(): void
    {
        $actual = new PdfFont(style: PdfFontStyle::ITALIC);
        $actual->bold();
        self::assertSame(PdfFontStyle::BOLD, $actual->getStyle());

        $actual = new PdfFont(style: PdfFontStyle::ITALIC);
        $actual->bold(true);
        self::assertSame(PdfFontStyle::BOLD_ITALIC, $actual->getStyle());
    }

    public function testConstructor(): void
    {
        $actual = new PdfFont();
        self::assertSame(PdfFontName::ARIAL, $actual->getName());
        self::assertSame(9.0, $actual->getSize());
        self::assertSame(PdfFontStyle::REGULAR, $actual->getStyle());
    }

    public function testDefault(): void
    {
        $actual = PdfFont::default();
        self::assertSame(PdfFontName::ARIAL, $actual->getName());
        self::assertSame(9.0, $actual->getSize());
        self::assertSame(PdfFontStyle::REGULAR, $actual->getStyle());
    }

    public function testIsDefaultSize(): void
    {
        $actual = new PdfFont();
        self::assertTrue($actual->isDefaultSize());

        $actual->setSize(12.0);
        self::assertFalse($actual->isDefaultSize());
    }

    public function testItalic(): void
    {
        $actual = new PdfFont(style: PdfFontStyle::BOLD);
        $actual->italic();
        self::assertSame(PdfFontStyle::ITALIC, $actual->getStyle());

        $actual = new PdfFont(style: PdfFontStyle::BOLD);
        $actual->italic(true);
        self::assertSame(PdfFontStyle::BOLD_ITALIC, $actual->getStyle());
    }

    public function testName(): void
    {
        $actual = new PdfFont();
        self::assertSame(PdfFontName::ARIAL, $actual->getName());
        $actual->setName(PdfFontName::HELVETICA);
        self::assertSame(PdfFontName::HELVETICA, $actual->getName());
    }

    public function testRegular(): void
    {
        $actual = new PdfFont(style: PdfFontStyle::BOLD);
        self::assertSame(PdfFontStyle::BOLD, $actual->getStyle());
        $actual->regular();
        self::assertSame(PdfFontStyle::REGULAR, $actual->getStyle());
    }

    public function testReset(): void
    {
        $actual = new PdfFont(PdfFontName::COURIER, 12.0, PdfFontStyle::BOLD);
        self::assertSame(PdfFontName::COURIER, $actual->getName());
        self::assertSame(12.0, $actual->getSize());
        self::assertSame(PdfFontStyle::BOLD, $actual->getStyle());

        $actual->reset();
        self::assertSame(PdfFontName::ARIAL, $actual->getName());
        self::assertSame(9.0, $actual->getSize());
        self::assertSame(PdfFontStyle::REGULAR, $actual->getStyle());
    }

    public function testStyle(): void
    {
        $actual = new PdfFont();
        self::assertSame(PdfFontStyle::REGULAR, $actual->getStyle());
        $actual->setStyle(PdfFontStyle::ITALIC);
        self::assertSame(PdfFontStyle::ITALIC, $actual->getStyle());
    }

    public function testUnderline(): void
    {
        $actual = new PdfFont();
        $actual->underline();
        self::assertSame(PdfFontStyle::UNDERLINE, $actual->getStyle());

        $actual = new PdfFont(style: PdfFontStyle::BOLD);
        $actual->underline(true);
        self::assertSame(PdfFontStyle::BOLD_UNDERLINE, $actual->getStyle());
    }
}
