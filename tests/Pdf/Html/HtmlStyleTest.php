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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlStyle::class)]
class HtmlStyleTest extends TestCase
{
    public static function getAlignments(): array
    {
        return [
            ['text-start', PdfTextAlignment::LEFT],
            ['text-end', PdfTextAlignment::RIGHT],
            ['text-center', PdfTextAlignment::CENTER],
            ['text-justify', PdfTextAlignment::JUSTIFIED],
        ];
    }

    public static function getBorders(): array
    {
        return [
            ['border-top', PdfBorder::top()],
            ['border-bottom', PdfBorder::bottom()],
            ['border-start', PdfBorder::left()],
            ['border-end', PdfBorder::right()],
            ['border-0', PdfBorder::none()],
            ['border-top-0', PdfBorder::all()->setTop(false)],
            ['border-start-0', PdfBorder::all()->setLeft(false)],
            ['border-end-0', PdfBorder::all()->setRight(false)],
            ['border-bottom-0', PdfBorder::all()->setBottom(false)],
        ];
    }

    public static function getMargins(): array
    {
        return [
            ['mt-1', 0.0, 0.0, 1.0, 0.0],
            ['mb-1', 0.0, 0.0, 0.0, 1.0],
            ['ms-1', 1.0, 0.0, 0.0, 0.0],
            ['me-1', 0.0, 1.0, 0.0, 0.0],
            ['mx-1', 1.0, 1.0, 0.0, 0.0],
            ['my-1', 0.0, 0.0, 1.0, 1.0],
            ['m-1', 1.0, 1.0, 1.0, 1.0],
        ];
    }

    #[DataProvider('getAlignments')]
    public function testAlignment(string $class, PdfTextAlignment $expected): void
    {
        $actual = HtmlStyle::default();
        $actual->update($class);
        self::assertSame($expected, $actual->getAlignment());
    }

    #[DataProvider('getBorders')]
    public function testBorder(string $class, PdfBorder $expected): void
    {
        $actual = HtmlStyle::default();
        $actual->update($class);
        self::assertEqualsCanonicalizing($expected, $actual->getBorder());
    }

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

    #[DataProvider('getMargins')]
    public function testMarginsWithClass(
        string $class,
        float $left,
        float $right,
        float $top,
        float $bottom,
    ): void {
        $actual = HtmlStyle::default();
        $actual->update($class);
        self::assertSame($left, $actual->getLeftMargin());
        self::assertSame($right, $actual->getRightMargin());
        self::assertSame($top, $actual->getTopMargin());
        self::assertSame($bottom, $actual->getBottomMargin());
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
