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
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfFontStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlStyleTest extends TestCase
{
    /**
     * @phpstan-return \Generator<int, array{string, PdfTextAlignment}>
     */
    public static function getAlignments(): \Generator
    {
        yield ['text-start', PdfTextAlignment::LEFT];
        yield ['text-end', PdfTextAlignment::RIGHT];
        yield ['text-center', PdfTextAlignment::CENTER];
        yield ['text-justify', PdfTextAlignment::JUSTIFIED];
    }

    /**
     * @phpstan-return \Generator<int, array{string, PdfBorder}>
     */
    public static function getBorders(): \Generator
    {
        yield ['border-top', PdfBorder::top()];
        yield ['border-bottom', PdfBorder::bottom()];
        yield ['border-start', PdfBorder::left()];
        yield ['border-end', PdfBorder::right()];
        yield ['border-0', PdfBorder::none()];
        yield ['border-top-0', PdfBorder::notTop()];
        yield ['border-start-0', PdfBorder::notLeft()];
        yield ['border-end-0', PdfBorder::notRight()];
        yield ['border-bottom-0', PdfBorder::notBottom()];
    }

    /**
     * @phpstan-return \Generator<int, array{string, float, float, float, float}>
     */
    public static function getMargins(): \Generator
    {
        yield ['mt-1', 0.0, 0.0, 1.0, 0.0];
        yield ['mb-1', 0.0, 0.0, 0.0, 1.0];
        yield ['ms-1', 1.0, 0.0, 0.0, 0.0];
        yield ['me-1', 0.0, 1.0, 0.0, 0.0];
        yield ['mx-1', 1.0, 1.0, 0.0, 0.0];
        yield ['my-1', 0.0, 0.0, 1.0, 1.0];
        yield ['m-1', 1.0, 1.0, 1.0, 1.0];
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
        $actual = HtmlStyle::default();
        $expected = 15.0;
        $actual->setMargins($expected);
        $this->assertSameMargins($actual, $expected);
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
        $this->assertSameMargins($actual);
    }

    public function testReset(): void
    {
        $expected = 10.0;
        $actual = HtmlStyle::default();
        $actual->setBottomMargin($expected);
        $actual->setLeftMargin($expected);
        $actual->setRightMargin($expected);
        $actual->setTopMargin($expected);
        $this->assertSameMargins($actual, $expected);

        $actual->reset();
        $this->assertSameMargins($actual);
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
        $this->assertSameMargins($actual);
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
        $style->update('text-decoration-none');
        $actual = $style->getFont()->getStyle();
        self::assertSame(PdfFontStyle::REGULAR, $actual);

        $style = HtmlStyle::default();
        $style->update('font-monospace');
        $actual = $style->getFont()->getName();
        self::assertSame(PdfFontName::COURIER, $actual);

        $style = HtmlStyle::default();
        $style->update('fs-1');
        $actual = $style->getFont()->getSize();
        self::assertSame(22.5, $actual);

        $style = HtmlStyle::default();
        $style->update('fs-2');
        $actual = $style->getFont()->getSize();
        self::assertSame(18.0, $actual);

        $style = HtmlStyle::default();
        $style->update('fs-3');
        $actual = $style->getFont()->getSize();
        self::assertSame(15.75, $actual);

        $style = HtmlStyle::default();
        $style->update('fs-4');
        $actual = $style->getFont()->getSize();
        self::assertSame(13.5, $actual);

        $style = HtmlStyle::default();
        $style->update('fs-5');
        $actual = $style->getFont()->getSize();
        self::assertSame(11.25, $actual);

        $style = HtmlStyle::default();
        $style->update('fs-6');
        $actual = $style->getFont()->getSize();
        self::assertSame(9.9, $actual);
    }

    public function testUpdateMargins(): void
    {
        $expected = 1.0;
        $actual = HtmlStyle::default();
        $actual->update('m-1');
        $this->assertSameMargins($actual, $expected);
    }

    private function assertSameMargins(HtmlStyle $actual, float $expected = 0.0): void
    {
        self::assertSame($expected, $actual->getBottomMargin());
        self::assertSame($expected, $actual->getLeftMargin());
        self::assertSame($expected, $actual->getRightMargin());
        self::assertSame($expected, $actual->getTopMargin());
    }
}
