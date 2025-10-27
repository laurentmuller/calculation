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

namespace App\Tests\Pdf\Colors;

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use fpdf\Color\PdfRgbColor;
use fpdf\PdfDocument;
use PHPUnit\Framework\TestCase;

final class PdfColorTest extends TestCase
{
    public function testApply(): void
    {
        $doc = new PdfDocument();
        PdfDrawColor::default()->apply($doc);
        PdfFillColor::default()->apply($doc);
        PdfTextColor::default()->apply($doc);
        $doc->close();
        self::assertSame(1, $doc->getPage());
    }

    public function testCellBorder(): void
    {
        $color = PdfDrawColor::cellBorder();
        $this->assertEqualValues($color, 221, 221, 221);
    }

    public function testDefaultColors(): void
    {
        $this->assertEqualColor(PdfDrawColor::black(), PdfDrawColor::default());
        $this->assertEqualColor(PdfFillColor::white(), PdfFillColor::default());
        $this->assertEqualColor(PdfTextColor::black(), PdfTextColor::default());
    }

    public function testHeader(): void
    {
        $color = PdfFillColor::header();
        $this->assertEqualValues($color, 245, 245, 245);
    }

    public function testIsFillColor(): void
    {
        $fill = new PdfFillColor(100, 100, 100);
        self::assertTrue($fill->isFillColor());
        $fill = new PdfFillColor(255, 255, 255);
        self::assertFalse($fill->isFillColor());
    }

    private function assertEqualColor(PdfRgbColor $color1, PdfRgbColor $color2): void
    {
        self::assertTrue($color1->equals($color2));
    }

    private function assertEqualValues(PdfRgbColor $color, int $red, int $green, int $blue): void
    {
        self::assertSame($color->red, $red);
        self::assertSame($color->green, $green);
        self::assertSame($color->blue, $blue);
    }
}
