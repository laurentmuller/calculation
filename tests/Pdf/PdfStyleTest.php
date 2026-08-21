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
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Html\HtmlColorName;
use App\Pdf\Html\HtmlGrayedColor;
use App\Pdf\PdfFont;
use App\Pdf\PdfLine;
use App\Pdf\PdfStyle;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfFontStyle;
use fpdf\PdfBorder;
use fpdf\PdfDocument;
use PHPUnit\Framework\TestCase;

final class PdfStyleTest extends TestCase
{
    public function testBlackHeaderStyle(): void
    {
        $actual = PdfStyle::getBlackHeaderStyle();
        self::assertSameStyle(
            actual: $actual,
            font: PdfFont::default()->bold(),
            drawColor: PdfDrawColor::black(),
            fillColor: PdfFillColor::black(),
            textColor: PdfTextColor::white(),
        );
    }

    public function testBoldCellStyle(): void
    {
        $actual = PdfStyle::getBoldCellStyle();
        self::assertSameStyle(
            actual: $actual,
            font: PdfFont::default()->bold(),
            drawColor: PdfDrawColor::cellBorder()
        );
    }

    public function testBulletStyle(): void
    {
        $actual = PdfStyle::getBulletStyle();
        self::assertSameStyle(
            actual: $actual,
            font: PdfFont::create(PdfFontName::SYMBOL),
            drawColor: PdfDrawColor::cellBorder(),
        );
    }

    public function testClone(): void
    {
        $source = PdfStyle::default()
            ->setFont(PdfFont::create(PdfFontName::SYMBOL, 12.0, PdfFontStyle::BOLD_ITALIC_UNDERLINE))
            ->setLine(PdfLine::create(12.0))
            ->setBorder(PdfBorder::bottom())
            ->setDrawColor(PdfDrawColor::darkGray())
            ->setFillColor(PdfFillColor::black())
            ->setTextColor(PdfTextColor::red());

        $actual = clone $source;

        self::assertSameStyle(
            actual: $actual,
            font: $source->getFont(),
            line: $source->getLine(),
            border: $source->getBorder(),
            drawColor: $source->getDrawColor(),
            fillColor: $source->getFillColor(),
            textColor: $source->getTextColor(),
        );
    }

    public function testConstructor(): void
    {
        $actual = new PdfStyle();
        self::assertSameStyle($actual);
    }

    public function testDefault(): void
    {
        $actual = PdfStyle::default();
        self::assertSameStyle($actual);
    }

    public function testDrawColorWithInterface(): void
    {
        $style = PdfStyle::default();
        $style->setDrawColor(HtmlColorName::ALICE_BLUE);
        $actual = $style->getDrawColor();
        $expected = HtmlColorName::ALICE_BLUE->getDrawColor();
        self::assertTrue($expected->equals($actual));
    }

    public function testFillColorWithInterface(): void
    {
        $style = PdfStyle::default();
        $style->setFillColor(HtmlBootstrapColor::WARNING);
        $actual = $style->getFillColor();
        $expected = HtmlBootstrapColor::WARNING->getFillColor();
        self::assertTrue($expected->equals($actual));
    }

    public function testHeaderStyle(): void
    {
        $actual = PdfStyle::getHeaderStyle();
        self::assertSameStyle(
            actual: $actual,
            font: PdfFont::create(style: PdfFontStyle::BOLD),
            drawColor: PdfDrawColor::cellBorder(),
            fillColor: PdfFillColor::header(),
        );
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
        self::assertSameStyle(
            actual: $actual,
            textColor: PdfTextColor::blue(),
        );
    }

    public function testNoBorderStyle(): void
    {
        $actual = PdfStyle::getNoBorderStyle();
        self::assertSameStyle(
            actual: $actual,
            border: PdfBorder::none(),
        );
    }

    public function testReset(): void
    {
        $actual = PdfStyle::getBlackHeaderStyle()
            ->setIndent(10.0)
            ->reset();
        self::assertSameStyle($actual);
    }

    public function testSetFont(): void
    {
        $font = PdfFont::default();
        $actual = PdfStyle::default();
        self::assertTrue($font->equals($actual->getFont()));

        $font->setStyle(PdfFontStyle::ITALIC);
        $actual->setFontItalic();
        self::assertTrue($font->equals($actual->getFont()));

        $font->setStyle(PdfFontStyle::REGULAR);
        $actual->setFontRegular();
        self::assertTrue($font->equals($actual->getFont()));

        $font->setSize(12.0);
        $actual->setFontSize(12.0);
        self::assertTrue($font->equals($actual->getFont()));

        $font->setStyle(PdfFontStyle::UNDERLINE);
        $actual->setFontUnderline();
        self::assertTrue($font->equals($actual->getFont()));

        $font->setStyle(PdfFontStyle::BOLD);
        $actual->setFontStyle(PdfFontStyle::BOLD);
        self::assertTrue($font->equals($actual->getFont()));
    }

    public function testTextColorWithInterface(): void
    {
        $style = PdfStyle::default();
        $style->setTextColor(HtmlGrayedColor::Gray100);
        $actual = $style->getTextColor();
        $expected = HtmlGrayedColor::Gray100->getTextColor();
        self::assertTrue($expected->equals($actual));
    }

    public function testUpdateDocument(): void
    {
        $actual = new PdfStyle();
        $document = new PdfDocument();
        $document->addPage();
        $actual->updateDocument($document);
        self::assertSame(1, $document->getPage());
    }

    protected static function assertSameStyle(
        PdfStyle $actual,
        ?PdfFont $font = null,
        ?PdfLine $line = null,
        ?PdfBorder $border = null,
        ?PdfDrawColor $drawColor = null,
        ?PdfFillColor $fillColor = null,
        ?PdfTextColor $textColor = null,
    ): void {
        self::assertTrue(($font ?? PdfFont::default())->equals($actual->getFont()));
        self::assertTrue(($line ?? PdfLine::default())->equals($actual->getLine()));
        self::assertTrue(($border ?? PdfBorder::all())->equals($actual->getBorder()));
        self::assertTrue(($drawColor ?? PdfDrawColor::default())->equals($actual->getDrawColor()));
        self::assertTrue(($fillColor ?? PdfFillColor::default())->equals($actual->getFillColor()));
        self::assertTrue(($textColor ?? PdfTextColor::default())->equals($actual->getTextColor()));
    }
}
