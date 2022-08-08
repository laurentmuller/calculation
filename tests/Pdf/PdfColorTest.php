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

use App\Pdf\AbstractPdfColor;
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfTextColor;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for PDF colors.
 */
class PdfColorTest extends TestCase
{
    public function getColors(): array
    {
        return [
            ['black', 0, 0, 0],
            ['blue', 0, 0, 255],
            ['cellBorder', 221, 221, 221],
            ['darkGreen', 0, 128, 0],
            ['green', 0, 255, 0],
            ['header', 245, 245, 245],
            ['link', 0, 0, 255],
            ['red', 255, 0, 0],
            ['white', 255, 255, 255],
        ];
    }

    public function getCreateColors(): \Generator
    {
        yield ['FFF', 255, 255, 255];
        yield ['FFFFFF', 255, 255, 255];
        yield ['#FFFFFF', 255, 255, 255];
        yield [[255, 255, 255], 255, 255, 255];
    }

    public function getCreateColorsInvalid(): \Generator
    {
        yield [''];
        yield [[255]];
        yield [[255, 255]];
        yield [[255, 255, 255, 255]];
    }

    public function getParseColors(): \Generator
    {
        yield ['FFF', 255, 255, 255];
        yield ['FFFFFF', 255, 255, 255];
        yield ['#FFFFFF', 255, 255, 255];
    }

    public function getParseColorsInvalid(): \Generator
    {
        yield [null];
        yield [''];
    }

    /**
     * @param int[]|string $rgb
     * @dataProvider getCreateColors
     */
    public function testCreate(array|string $rgb, int $red, int $green, int $blue): void
    {
        $color = PdfTextColor::create($rgb);
        $this->validateColor($color, $red, $green, $blue);
    }

    /**
     * @param int[]|string $rgb
     * @dataProvider getCreateColorsInvalid
     */
    public function testCreateInvalid(array|string $rgb): void
    {
        $color = PdfTextColor::create($rgb);
        self::assertNull($color);
    }

    /**
     * @dataProvider getColors
     */
    public function testDrawColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfDrawColor $color */
        $color = PdfDrawColor::$name();
        $this->validateColor($color, $red, $green, $blue);
    }

    /**
     * @dataProvider getColors
     */
    public function testFillColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfFillColor $color */
        $color = PdfFillColor::$name();
        $this->validateColor($color, $red, $green, $blue);
    }

    /**
     * @dataProvider getParseColors
     */
    public function testParse(?string $value, int $red, int $green, int $blue): void
    {
        $rgb = PdfTextColor::parse($value);
        self::assertIsArray($rgb);
        self::assertCount(3, $rgb);
        self::assertEquals($red, $rgb[0]);
        self::assertEquals($green, $rgb[1]);
        self::assertEquals($blue, $rgb[2]);
    }

    /**
     * @dataProvider getParseColorsInvalid
     */
    public function testParseInvalid(?string $value): void
    {
        $color = PdfFillColor::parse($value);
        self::assertFalse($color);
    }

    /**
     * @dataProvider getColors
     */
    public function testTextColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfTextColor $color */
        $color = PdfTextColor::$name();
        $this->validateColor($color, $red, $green, $blue);
    }

    private function validateColor(?AbstractPdfColor $color, int $red, int $green, int $blue): void
    {
        self::assertNotNull($color);
        self::assertEquals($red, $color->getRed());
        self::assertEquals($green, $color->getGreen());
        self::assertEquals($blue, $color->getBlue());
    }
}
