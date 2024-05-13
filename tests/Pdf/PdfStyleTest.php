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

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfDocument;
use App\Pdf\PdfFont;
use App\Pdf\PdfLine;
use App\Pdf\PdfStyle;
use fpdf\PdfBorder;
use fpdf\PdfFontName;
use fpdf\PdfFontStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStyle::class)]
class PdfStyleTest extends TestCase
{
    public function testApply(): void
    {
        $actual = new PdfStyle();
        $document = new PdfDocument();
        $document->addPage();
        $actual->apply($document);
        self::assertSame(1, $document->getPage());
    }

    public function testBlackHeaderStyle(): void
    {
        $font = PdfFont::default()->bold();
        $actual = PdfStyle::getBlackHeaderStyle();
        self::assertEqualsCanonicalizing($font, $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::black(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::black(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::white(), $actual->getTextColor());
    }

    public function testBoldCellStyle(): void
    {
        $font = PdfFont::default()->bold();
        $actual = PdfStyle::getBoldCellStyle();
        self::assertEqualsCanonicalizing($font, $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::cellBorder(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }

    public function testBulletStyle(): void
    {
        $font = PdfFont::default()->setName(PdfFontName::SYMBOL);
        $actual = PdfStyle::getBulletStyle();
        self::assertEqualsCanonicalizing($font, $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::cellBorder(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }

    public function testClone(): void
    {
        $actual = new PdfStyle();
        $clone = clone $actual;
        self::assertEqualsCanonicalizing($clone->getFont(), $actual->getFont());
        self::assertEqualsCanonicalizing($clone->getLine(), $actual->getLine());
        self::assertEqualsCanonicalizing($clone->getBorder(), $actual->getBorder());
        self::assertEqualsCanonicalizing($clone->getDrawColor(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing($clone->getFillColor(), $actual->getFillColor());
    }

    public function testConstructor(): void
    {
        $actual = new PdfStyle();
        self::assertEqualsCanonicalizing(PdfFont::default(), $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::default(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }

    public function testDefault(): void
    {
        $actual = PdfStyle::default();
        self::assertEqualsCanonicalizing(PdfFont::default(), $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::default(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }

    public function testHeaderStyle(): void
    {
        $font = PdfFont::default()->bold();
        $actual = PdfStyle::getHeaderStyle();
        self::assertEqualsCanonicalizing($font, $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::cellBorder(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::header(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }

    public function testIndent(): void
    {
        $actual = PdfStyle::default();
        self::assertSame(0.0, $actual->getIndent());
        $actual->setIndent(10.0);
        self::assertSame(10.0, $actual->getIndent());
    }

    public function testIsFillColor(): void
    {
        $actual = PdfStyle::default();
        self::assertFalse($actual->isFillColor());
        $actual->setFillColor(PdfFillColor::black());
        self::assertTrue($actual->isFillColor());
    }

    public function testLinkStyle(): void
    {
        $actual = PdfStyle::getLinkStyle();
        self::assertEqualsCanonicalizing(PdfFont::default(), $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::default(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::link(), $actual->getTextColor());
    }

    public function testNoBorderStyle(): void
    {
        $actual = PdfStyle::getNoBorderStyle();
        self::assertEqualsCanonicalizing(PdfFont::default(), $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::none(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::default(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }

    public function testReset(): void
    {
        $actual = PdfStyle::getBlackHeaderStyle();
        $actual->setIndent(10.0)
            ->reset();
        self::assertEqualsCanonicalizing(PdfFont::default(), $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::default(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }

    public function testSetFont(): void
    {
        $font = PdfFont::default();
        $actual = PdfStyle::default();
        self::assertEqualsCanonicalizing($font, $actual->getFont());

        $font = PdfFont::default()->italic();
        $actual->setFontItalic();
        self::assertEqualsCanonicalizing($font, $actual->getFont());

        $font = PdfFont::default();
        $actual->setFontRegular();
        self::assertEqualsCanonicalizing($font, $actual->getFont());

        $font = PdfFont::default()->underline();
        $actual->setFontUnderline();
        self::assertEqualsCanonicalizing($font, $actual->getFont());

        $font = PdfFont::default()->setStyle(PdfFontStyle::BOLD);
        $actual->setFontStyle(PdfFontStyle::BOLD);
        self::assertEqualsCanonicalizing($font, $actual->getFont());
    }
}
