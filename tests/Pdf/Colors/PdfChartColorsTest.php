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

class PdfChartColorsTest extends TestCase
{
    private static PdfChartColors $colors;

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        self::$colors = new PdfChartColors();
    }

    /**
     * @return \Generator<int, array{int, int, int}>
     */
    public static function getColors(): \Generator
    {
        yield [54, 162, 235]; // blue
        yield [255, 99, 132]; // red
        yield [255, 159, 64]; // orange
        yield [255, 205, 86]; // yellow
        yield [75, 192, 192]; // green
        yield [153, 102, 255]; // purple
        yield [201, 203, 207]; // grey
        // new cycle
        yield [54, 162, 235]; // blue
    }

    #[DataProvider('getColors')]
    public function testNext(int $red, int $green, int $blue): void
    {
        $actual = self::$colors->next();
        self::assertSameColor($actual, $red, $green, $blue);
    }

    public function testReset(): void
    {
        $colors = new PdfChartColors();
        $actual = $colors->next();
        self::assertSameColor($actual, 54, 162, 235);
        $colors->reset();
        $actual = $colors->next();
        self::assertSameColor($actual, 54, 162, 235);
    }

    protected static function assertSameColor(PdfRgbColor $actual, int $red, int $green, int $blue): void
    {
        self::assertSame($red, $actual->red);
        self::assertSame($green, $actual->green);
        self::assertSame($blue, $actual->blue);
    }
}
