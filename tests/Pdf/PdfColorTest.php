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

use App\Pdf\Colors\AbstractPdfColor;
use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PdfDrawColor::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(PdfFillColor::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(PdfTextColor::class)]
class PdfColorTest extends TestCase
{
    public static function getColorsInvalid(): \Generator
    {
        yield [''];
        yield [null];
        yield [[255]];
        yield [[255, 255]];
        yield [[255, 255, 255, 255]];
        yield ['xyz'];
    }

    public static function getColorsValid(): \Generator
    {
        yield ['FFF', 255, 255, 255];
        yield ['FFFFFF', 255, 255, 255];
        yield ['#FFFFFF', 255, 255, 255];
        yield [[255, 255, 255], 255, 255, 255];
    }

    public static function getNamedColors(): array
    {
        return [
            ['black', 0, 0, 0],
            ['blue', 0, 0, 255],
            ['cellBorder', 221, 221, 221],
            ['darkGray', 169, 169, 169],
            ['darkGreen', 0, 128, 0],
            ['darkRed', 128, 0, 0],
            ['green', 0, 255, 0],
            ['header', 245, 245, 245],
            ['link', 0, 0, 255],
            ['red', 255, 0, 0],
            ['white', 255, 255, 255],
        ];
    }

    /**
     * @param int[]|string $rgb
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getColorsInvalid')]
    public function testColorsInvalid(array|string|null $rgb): void
    {
        $color = PdfTextColor::create($rgb);
        self::assertNull($color);
    }

    /**
     * @param int[]|string $rgb
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getColorsValid')]
    public function testColorsValid(array|string $rgb, int $red, int $green, int $blue): void
    {
        $color = PdfTextColor::create($rgb);
        $this->validateColor($color, $red, $green, $blue);
    }

    public function testDefaultColors(): void
    {
        self::assertEqualsCanonicalizing(PdfDrawColor::black(), PdfDrawColor::default());
        self::assertEqualsCanonicalizing(PdfFillColor::white(), PdfFillColor::default());
        self::assertEqualsCanonicalizing(PdfTextColor::black(), PdfTextColor::default());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNamedColors')]
    public function testDrawColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfDrawColor $color */
        $color = PdfDrawColor::$name();
        $this->validateColor($color, $red, $green, $blue);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNamedColors')]
    public function testFillColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfFillColor $color */
        $color = PdfFillColor::$name();
        $this->validateColor($color, $red, $green, $blue);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNamedColors')]
    public function testTextColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfTextColor $color */
        $color = PdfTextColor::$name();
        $this->validateColor($color, $red, $green, $blue);
    }

    private function validateColor(?AbstractPdfColor $color, int $red, int $green, int $blue): void
    {
        self::assertNotNull($color);
        self::assertSame($red, $color->red);
        self::assertSame($green, $color->green);
        self::assertSame($blue, $color->blue);
    }
}
