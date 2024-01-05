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

        //        $rgb = [0xFF, 0x00, 0x00];
        //        yield [$rgb[0], $rgb[1], $rgb[2], 'ff0000'];
        //        yield [$rgb[0], $rgb[1], $rgb[2], '0xff0000', '0x'];
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

    /**
     * @psalm-param int<0, 255> $red
     * @psalm-param int<0, 255> $green
     * @psalm-param int<0, 255> $blue
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getHexColors')]
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
    #[\PHPUnit\Framework\Attributes\DataProvider('getIntColors')]
    public function testAsInt(int $red, int $green, int $blue, int $expected): void
    {
        $color = new PdfDrawColor($red, $green, $blue);
        $actual = $color->asInt();
        self::assertSame($expected, $actual);
    }

    public function testDefaultColors(): void
    {
        self::assertEqualColor(PdfDrawColor::black(), PdfDrawColor::default());
        self::assertEqualColor(PdfFillColor::white(), PdfFillColor::default());
        self::assertEqualColor(PdfTextColor::black(), PdfTextColor::default());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNamedColors')]
    public function testDrawColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfDrawColor $color */
        $color = PdfDrawColor::$name();
        self::assertEqualValues($color, $red, $green, $blue);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNamedColors')]
    public function testFillColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfFillColor $color */
        $color = PdfFillColor::$name();
        self::assertEqualValues($color, $red, $green, $blue);
    }

    /**
     * @psalm-param int<0, 255>[]|int|string|null $rgb
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getInvalidColors')]
    public function testInvalidColors(array|int|string|null $rgb): void
    {
        $color = PdfTextColor::create($rgb);
        self::assertNull($color);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNamedColors')]
    public function testTextColor(string $name, int $red, int $green, int $blue): void
    {
        /** @var PdfTextColor $color */
        $color = PdfTextColor::$name();
        self::assertEqualValues($color, $red, $green, $blue);
    }

    /**
     * @psalm-param int<0, 255>[]|int|string|null $rgb
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getValidColors')]
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
