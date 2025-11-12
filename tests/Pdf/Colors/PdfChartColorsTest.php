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

use App\Pdf\Colors\PdfChartColors;
use fpdf\Color\PdfRgbColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PdfChartColorsTest extends TestCase
{
    public static function getColors(): \Generator
    {
        $colors = new PdfChartColors();
        yield [$colors, 54, 162, 235]; // blue
        yield [$colors, 255, 99, 132]; // red
        yield [$colors, 255, 159, 64]; // orange
        yield [$colors, 255, 205, 86]; // yellow
        yield [$colors, 75, 192, 192]; // green
        yield [$colors, 153, 102, 255]; // purple
        yield [$colors, 201, 203, 207]; // grey
        // new cycle
        yield [$colors, 54, 162, 235]; // blue
    }

    public function testCount(): void
    {
        $expected = 7;
        $colors = new PdfChartColors();
        $actual = $colors->count();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getColors')]
    public function testNext(PdfChartColors $colors, int $red, int $green, int $blue): void
    {
        $actual = $colors->next();
        $this->assertSameColor($actual, $red, $green, $blue);
    }

    public function testReset(): void
    {
        $colors = new PdfChartColors();
        $actual = $colors->next();
        $this->assertSameColor($actual, 54, 162, 235);
        $colors->reset();
        $actual = $colors->next();
        $this->assertSameColor($actual, 54, 162, 235);
    }

    private function assertSameColor(PdfRgbColor $actual, int $red, int $green, int $blue): void
    {
        self::assertSame($red, $actual->red);
        self::assertSame($green, $actual->green);
        self::assertSame($blue, $actual->blue);
    }
}
