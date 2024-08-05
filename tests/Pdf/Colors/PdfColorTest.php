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

use App\Pdf\Colors\AbstractPdfColor;
use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use fpdf\PdfDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PdfColorTest extends TestCase
{
    public static function getHexColors(): \Generator
    {
        $rgb = [0x00, 0x00, 0x00];
        yield [$rgb[0], $rgb[1], $rgb[2], '000000'];
        yield [$rgb[0], $rgb[1], $rgb[2], '0x000000', '0x'];

        $rgb = [0xFF, 0xFF, 0xFF];
        yield [$rgb[0], $rgb[1], $rgb[2], 'ffffff'];
        yield [$rgb[0], $rgb[1], $rgb[2], '0xffffff', '0x'];

        $rgb = [0x32, 0x64, 0x96];
        yield [$rgb[0], $rgb[1], $rgb[2], '326496'];
        yield [$rgb[0], $rgb[1], $rgb[2], '0x326496', '0x'];

        $rgb = [0x00, 0x64, 0x96];
        yield [$rgb[0], $rgb[1], $rgb[2], '006496'];
        yield [$rgb[0], $rgb[1], $rgb[2], '0x006496', '0x'];

        $rgb = [0x00, 0xFF, 0x00];
        yield [$rgb[0], $rgb[1], $rgb[2], '00ff00'];
        yield [$rgb[0], $rgb[1], $rgb[2], '0x00ff00', '0x'];

        $rgb = [0x00, 0x00, 0xFF];
        yield [$rgb[0], $rgb[1], $rgb[2], '0000ff'];
        yield [$rgb[0], $rgb[1], $rgb[2], '0x0000ff', '0x'];
    }

    public static function getIntColors(): \Generator
    {
        $rgb = [0, 0, 0];
        $value = (($rgb[0] & 0xFF) << 0x10) | (($rgb[1] & 0xFF) << 0x8) | ($rgb[2] & 0xFF);
        yield [$rgb[0], $rgb[1], $rgb[2], $value];

        $rgb = [255, 255, 255];
        $value = (($rgb[0] & 0xFF) << 0x10) | (($rgb[1] & 0xFF) << 0x8) | ($rgb[2] & 0xFF);
        yield [$rgb[0], $rgb[1], $rgb[2], $value];

        $rgb = [50, 100, 150];
        $value = (($rgb[0] & 0xFF) << 0x10) | (($rgb[1] & 0xFF) << 0x8) | ($rgb[2] & 0xFF);
        yield [$rgb[0], $rgb[1], $rgb[2], $value];
    }

    public static function getInvalidColors(): \Generator
    {
        yield [''];
        yield [null];
        yield [[255]];
        yield [[255, 255]];
        yield [[255, 255, 255, 255]];
        yield ['xyz'];
        yield ['0xFFF'];
    }

    public static function getNamedColors(): \Iterator
    {
        yield ['black', 0, 0, 0];
        yield ['blue', 0, 0, 255];
        yield ['cellBorder', 221, 221, 221];
        yield ['darkGray', 169, 169, 169];
        yield ['darkGreen', 0, 128, 0];
        yield ['darkRed', 128, 0, 0];
        yield ['green', 0, 255, 0];
        yield ['header', 245, 245, 245];
        yield ['link', 0, 0, 255];
        yield ['red', 255, 0, 0];
        yield ['white', 255, 255, 255];
    }

    public static function getValidColors(): \Generator
    {
        yield ['fff', 255, 255, 255];
        yield ['FFF', 255, 255, 255];
        yield ['FfF', 255, 255, 255];
        yield ['ffffff', 255, 255, 255];
        yield ['FFFFFF', 255, 255, 255];
        yield ['fffFFF', 255, 255, 255];
        yield ['#FFFFFF', 255, 255, 255];
        yield [[255, 255, 255], 255, 255, 255];

        $rgb = [255, 255, 255];
        $value = (($rgb[0] & 0xFF) << 0x10) | (($rgb[1] & 0xFF) << 0x8) | ($rgb[2] & 0xFF);
        yield [$value, $rgb[0], $rgb[1], $rgb[2]];

        $rgb = [50, 100, 150];
        $value = (($rgb[0] & 0xFF) << 0x10) | (($rgb[1] & 0xFF) << 0x8) | ($rgb[2] & 0xFF);
        yield [$value, $rgb[0], $rgb[1], $rgb[2]];
    }

    public function testApply(): void
    {
        $doc = new PdfDocument();
        $draw = new PdfDrawColor(100, 100, 100);
        $fill = new PdfFillColor(100, 100, 100);
        $text = new PdfTextColor(100, 100, 100);
        $draw->apply($doc);
        $fill->apply($doc);
        $text->apply($doc);
        $doc->close();
        self::assertSame(1, $doc->getPage());
    }

    /**
     * @psalm-param int<0, 255> $red
     * @psalm-param int<0, 255> $green
     * @psalm-param int<0, 255> $blue
     */
    #[DataProvider('getHexColors')]
    public function testAsHex(int $red, int $green, int $blue, string $expected, string $prefix = ''): void
    {
        $color = new PdfDrawColor($red, $green, $blue);
        $actual = $color->asHex($prefix);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param int<0, 255> $red
     * @psalm-param int<0, 255> $green
     * @psalm-param int<0, 255> $blue
     */
    #[DataProvider('getIntColors')]
    public function testAsInt(int $red, int $green, int $blue, int $expected): void
    {
        $color = new PdfDrawColor($red, $green, $blue);
        $actual = $color->asInt();
        self::assertSame($expected, $actual);
    }

    public function testColorBlack(): void
    {
        $color = PdfFillColor::black();
        self::assertEqualValues($color, 0, 0, 0);
    }

    public function testColorBlue(): void
    {
        $color = PdfFillColor::blue();
        self::assertEqualValues($color, 0, 0, 255);
    }

    public function testColorCellBorder(): void
    {
        $color = PdfFillColor::cellBorder();
        self::assertEqualValues($color, 221, 221, 221);
    }

    public function testColorDarkGray(): void
    {
        $color = PdfFillColor::darkGray();
        self::assertEqualValues($color, 169, 169, 169);
    }

    public function testColorDarkGreen(): void
    {
        $color = PdfFillColor::darkGreen();
        self::assertEqualValues($color, 0, 128, 0);
    }

    public function testColorGreen(): void
    {
        $color = PdfFillColor::green();
        self::assertEqualValues($color, 0, 255, 0);
    }

    public function testColorHeader(): void
    {
        $color = PdfFillColor::header();
        self::assertEqualValues($color, 245, 245, 245);
    }

    public function testColorLink(): void
    {
        $color = PdfFillColor::link();
        self::assertEqualValues($color, 0, 0, 255);
    }

    public function testColorRed(): void
    {
        $color = PdfFillColor::red();
        self::assertEqualValues($color, 255, 0, 0);
    }

    public function testColorWhite(): void
    {
        $color = PdfFillColor::white();
        self::assertEqualValues($color, 255, 255, 255);
    }

    public function testDefaultColors(): void
    {
        self::assertEqualColor(PdfDrawColor::black(), PdfDrawColor::default());
        self::assertEqualColor(PdfFillColor::white(), PdfFillColor::default());
        self::assertEqualColor(PdfTextColor::black(), PdfTextColor::default());
    }

    #[DataProvider('getNamedColors')]
    public function testDrawColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfDrawColor $color */
        $color = PdfDrawColor::$name();
        self::assertEqualValues($color, $red, $green, $blue);
    }

    #[DataProvider('getNamedColors')]
    public function testFillColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfFillColor $color */
        $color = PdfFillColor::$name();
        self::assertEqualValues($color, $red, $green, $blue);
    }

    /**
     * @psalm-param int<0, 255>[]|int|string|null $rgb
     */
    #[DataProvider('getInvalidColors')]
    public function testInvalidColors(array|int|string|null $rgb): void
    {
        $color = PdfTextColor::create($rgb);
        self::assertNull($color);
    }

    public function testIsFillColor(): void
    {
        $fill = new PdfFillColor(100, 100, 100);
        self::assertTrue($fill->isFillColor());
        $fill = new PdfFillColor(255, 255, 255);
        self::assertFalse($fill->isFillColor());
    }

    #[DataProvider('getNamedColors')]
    public function testTextColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfTextColor $color */
        $color = PdfTextColor::$name();
        self::assertEqualValues($color, $red, $green, $blue);
    }

    /**
     * @psalm-param int<0, 255>[]|int|string|null $rgb
     */
    #[DataProvider('getValidColors')]
    public function testValidColors(array|int|string|null $rgb, int $red, int $green, int $blue): void
    {
        $color = PdfTextColor::create($rgb);
        self::assertEqualValues($color, $red, $green, $blue);
    }

    protected static function assertEqualColor(AbstractPdfColor $color1, AbstractPdfColor $color2): void
    {
        self::assertSame($color1::class, $color2::class);
        self::assertSame($color1->red, $color2->red);
        self::assertSame($color1->green, $color2->green);
        self::assertSame($color1->blue, $color2->blue);
    }

    protected static function assertEqualValues(?AbstractPdfColor $color, int $red, int $green, int $blue): void
    {
        self::assertNotNull($color);
        self::assertSame($color->red, $red);
        self::assertSame($color->green, $green);
        self::assertSame($color->blue, $blue);
    }
}
