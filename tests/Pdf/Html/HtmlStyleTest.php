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

namespace App\Tests\Pdf\Html;

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Html\HtmlStyle;
use fpdf\PdfBorder;
use fpdf\PdfFontName;
use fpdf\PdfFontStyle;
use fpdf\PdfTextAlignment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlStyle::class)]
class HtmlStyleTest extends TestCase
{
    public function testMargins(): void
    {
        $expected = 5.0;
        $actual = HtmlStyle::default();
        $actual->setXMargins($expected)
            ->setYMargins($expected);
        self::assertSameMargins($actual, $expected);

        $expected = 15.0;
        $actual->setMargins($expected);
        self::assertSameMargins($actual, $expected);
    }

    public function testProperties(): void
    {
        $actual = HtmlStyle::default();
        self::assertSame(PdfTextAlignment::LEFT, $actual->getAlignment());
        self::assertSameMargins($actual);
    }

    public function testReset(): void
    {
        $expected = 10.0;
        $actual = HtmlStyle::default();
        $actual->setBottomMargin($expected);
        $actual->setLeftMargin($expected);
        $actual->setRightMargin($expected);
        $actual->setTopMargin($expected);
        self::assertSameMargins($actual, $expected);

        $actual->reset();
        self::assertSameMargins($actual);
    }

    public function testUpdateAlignment(): void
    {
        $actual = HtmlStyle::default();
        $actual->update('text-justify');
        self::assertSame(PdfTextAlignment::JUSTIFIED, $actual->getAlignment());
    }

    public function testUpdateBorder(): void
    {
        $actual = HtmlStyle::default();
        $actual->update('border');
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
    }

    public function testUpdateColor(): void
    {
        $actual = HtmlStyle::default();
        $actual->update('primary');

        $rgb = HtmlBootstrapColor::PRIMARY->asRGB();
        $color = new PdfTextColor($rgb[0], $rgb[1], $rgb[2]);
        self::assertEqualsCanonicalizing($color, $actual->getTextColor());

        $color = new PdfFillColor($rgb[0], $rgb[1], $rgb[2]);
        self::assertEqualsCanonicalizing($color, $actual->getFillColor());

        $color = new PdfDrawColor($rgb[0], $rgb[1], $rgb[2]);
        self::assertEqualsCanonicalizing($color, $actual->getDrawColor());
    }

    public function testUpdateEmpty(): void
    {
        $actual = HtmlStyle::default();
        $actual->update('');
        self::assertSameMargins($actual);
    }

    public function testUpdateFont(): void
    {
        $style = HtmlStyle::default();
        $style->update('fw-bold');
        $actual = $style->getFont()->getStyle();
        self::assertSame(PdfFontStyle::BOLD, $actual);

        $style = HtmlStyle::default();
        $style->update('fst-normal');
        $actual = $style->getFont()->getStyle();
        self::assertSame(PdfFontStyle::REGULAR, $actual);

        $style = HtmlStyle::default();
        $style->update('fst-italic');
        $actual = $style->getFont()->getStyle();
        self::assertSame(PdfFontStyle::ITALIC, $actual);

        $style = HtmlStyle::default();
        $style->update('text-decoration-underline');
        $actual = $style->getFont()->getStyle();
        self::assertSame(PdfFontStyle::UNDERLINE, $actual);

        $style = HtmlStyle::default();
        $style->update('font-monospace');
        $actual = $style->getFont()->getName();
        self::assertSame(PdfFontName::COURIER, $actual);
    }

    public function testUpdateMargins(): void
    {
        $expected = 1.0;
        $actual = HtmlStyle::default();
        $actual->update('m-1');
        self::assertSameMargins($actual, $expected);
    }

    protected static function assertSameMargins(HtmlStyle $actual, float $expected = 0.0): void
    {
        self::assertSame($expected, $actual->getBottomMargin());
        self::assertSame($expected, $actual->getLeftMargin());
        self::assertSame($expected, $actual->getRightMargin());
        self::assertSame($expected, $actual->getTopMargin());
    }
}
