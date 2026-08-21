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

final class PdfFontTest extends TestCase
{
    public function testBold(): void
    {
        $actual = new PdfFont(style: PdfFontStyle::ITALIC);
        $actual->bold();
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::BOLD
        );

        $actual = new PdfFont(style: PdfFontStyle::ITALIC);
        $actual->bold(true);
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::BOLD_ITALIC
        );
    }

    public function testConstructor(): void
    {
        $actual = new PdfFont();
        self::assertSame(PdfFontName::ARIAL, $actual->getName());
        self::assertSame(9.0, $actual->getSize());
        self::assertSame(PdfFontStyle::REGULAR, $actual->getStyle());
    }

    public function testCreate(): void
    {
        $actual = PdfFont::create(
            name: PdfFontName::SYMBOL,
            size: 12.0,
            style: PdfFontStyle::BOLD_ITALIC,
        );
        self::assertSameFont(
            actual: $actual,
            name: PdfFontName::SYMBOL,
            size: 12.0,
            style: PdfFontStyle::BOLD_ITALIC
        );
    }

    public function testDefault(): void
    {
        $actual = PdfFont::default();
        self::assertSameFont($actual);
    }

    public function testEquals(): void
    {
        $expected = PdfFont::default();
        $actual = new PdfFont();
        self::assertTrue($expected->equals($actual));

        $actual = PdfFont::create(PdfFontName::SYMBOL);
        self::assertFalse($expected->equals($actual));
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
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::ITALIC
        );

        $actual = new PdfFont(style: PdfFontStyle::BOLD);
        $actual->italic(true);
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::BOLD_ITALIC
        );
    }

    public function testName(): void
    {
        $actual = PdfFont::default()
            ->setName(PdfFontName::HELVETICA);
        self::assertSameFont(
            actual: $actual,
            name: PdfFontName::HELVETICA
        );
    }

    public function testRegular(): void
    {
        $actual = new PdfFont(style: PdfFontStyle::BOLD);
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::BOLD
        );
        $actual->regular();
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::REGULAR
        );
    }

    public function testReset(): void
    {
        $actual = new PdfFont(PdfFontName::COURIER, 12.0, PdfFontStyle::BOLD);
        self::assertSameFont(
            actual: $actual,
            name: PdfFontName::COURIER,
            size: 12.0,
            style: PdfFontStyle::BOLD
        );

        $actual->reset();
        self::assertSameFont($actual);
    }

    public function testSetStyle(): void
    {
        $actual = PdfFont::default()
            ->setStyle(PdfFontStyle::BOLD);
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::BOLD
        );

        $actual->setStyle();
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::REGULAR
        );
    }

    public function testStyle(): void
    {
        $actual = PdfFont::default()
            ->italic();
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::ITALIC
        );
    }

    public function testUnderline(): void
    {
        $actual = PdfFont::default()
            ->underline();
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::UNDERLINE
        );

        $actual = new PdfFont(style: PdfFontStyle::BOLD);
        $actual->underline(true);
        self::assertSameFont(
            actual: $actual,
            style: PdfFontStyle::BOLD_UNDERLINE
        );
    }

    public function testUpdateDocument(): void
    {
        $document = new PdfDocument();
        $actual = new PdfFont();
        $actual->updateDocument($document);
        self::assertSame(0, $document->getPage());
    }

    protected static function assertSameFont(
        PdfFont $actual,
        ?PdfFontName $name = PdfFont::DEFAULT_NAME,
        ?float $size = PdfFont::DEFAULT_SIZE,
        ?PdfFontStyle $style = PdfFont::DEFAULT_STYLE
    ): void {
        self::assertSame($name, $actual->getName());
        self::assertSame($size, $actual->getSize());
        self::assertSame($style, $actual->getStyle());
    }
}
