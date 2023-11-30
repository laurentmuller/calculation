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

namespace App\Tests\Enums;

use App\Enums\ImageExtension;
use App\Utils\FileUtils;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ImageExtension::class)]
class ImageExtensionTest extends TestCase
{
    public static function getFilters(): array
    {
        return [
            [ImageExtension::BMP, '*.bmp'],
            [ImageExtension::GIF, '*.gif'],
            [ImageExtension::JPEG, '*.jpeg'],
            [ImageExtension::JPG, '*.jpg'],
            [ImageExtension::PNG, '*.png'],
            [ImageExtension::WBMP, '*.wbmp'],
            [ImageExtension::WEBP, '*.webp'],
            [ImageExtension::XBM, '*.xbm'],
            [ImageExtension::XPM, '*.xpm'],
        ];
    }

    public static function getImageTypes(): array
    {
        return [
            [ImageExtension::BMP,  \IMAGETYPE_BMP],
            [ImageExtension::GIF, \IMAGETYPE_GIF],
            [ImageExtension::JPEG, \IMAGETYPE_JPEG],
            [ImageExtension::JPG, \IMAGETYPE_JPEG],
            [ImageExtension::PNG, \IMAGETYPE_PNG],
            [ImageExtension::WBMP,  \IMAGETYPE_WBMP],
            [ImageExtension::WEBP,  \IMAGETYPE_WEBP],
            [ImageExtension::XBM,  \IMAGETYPE_XBM],
            [ImageExtension::XPM,  \IMAGETYPE_UNKNOWN],
        ];
    }

    public static function getInvalidOptions(): \Generator
    {
        $values = ImageExtension::cases();
        foreach ($values as $value) {
            yield [$value, ['fake' => 100]];
        }
    }

    public static function getTryFromTypes(): array
    {
        return [
            [\IMAGETYPE_BMP, ImageExtension::BMP],
            [\IMAGETYPE_GIF, ImageExtension::GIF],
            [\IMAGETYPE_JPEG, ImageExtension::JPEG],
            [\IMAGETYPE_PNG, ImageExtension::PNG],
            [\IMAGETYPE_WBMP, ImageExtension::WBMP],
            [\IMAGETYPE_WEBP, ImageExtension::WEBP],
            [\IMAGETYPE_XBM, ImageExtension::XBM],
            [\IMAGETYPE_UNKNOWN],
            [-1],
        ];
    }

    public static function getTypes(): array
    {
        return [
            [ImageExtension::BMP, \IMAGETYPE_BMP],
            [ImageExtension::GIF, \IMAGETYPE_GIF],
            [ImageExtension::JPEG, \IMAGETYPE_JPEG],
            [ImageExtension::JPG, \IMAGETYPE_JPEG],
            [ImageExtension::PNG, \IMAGETYPE_PNG],
            [ImageExtension::WBMP, \IMAGETYPE_WBMP],
            [ImageExtension::WEBP, \IMAGETYPE_WEBP],
            [ImageExtension::XBM, \IMAGETYPE_XBM],
            [ImageExtension::XPM, \IMAGETYPE_UNKNOWN],
        ];
    }

    public static function getValidOptions(): \Generator
    {
        yield [ImageExtension::BMP, ['compressed' => true]];
        yield [ImageExtension::BMP, ['compressed' => false]];

        $quality = ['quality' => -1];
        yield [ImageExtension::JPEG, $quality];
        yield [ImageExtension::JPG, $quality];
        yield [ImageExtension::WEBP, $quality];

        yield [ImageExtension::PNG, $quality];
        yield [ImageExtension::PNG, ['filters' => -1]];
        yield [ImageExtension::PNG, ['quality' => -1, 'filters' => -1]];

        $foreground = ['foreground_color' => null];
        yield [ImageExtension::WBMP, $foreground];
        yield [ImageExtension::XBM, $foreground];

        yield [ImageExtension::XPM, [], false];
    }

    public static function getValues(): array
    {
        return [
            [ImageExtension::BMP, 'bmp'],
            [ImageExtension::GIF, 'gif'],
            [ImageExtension::JPEG, 'jpeg'],
            [ImageExtension::JPG, 'jpg'],
            [ImageExtension::PNG, 'png'],
            [ImageExtension::WBMP, 'wbmp'],
            [ImageExtension::WEBP, 'webp'],
            [ImageExtension::XBM, 'xbm'],
            [ImageExtension::XPM, 'xpm'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(9, ImageExtension::cases());
    }

    public function testDefault(): void
    {
        $actual = ImageExtension::getDefault();
        self::assertSame(ImageExtension::PNG, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getFilters')]
    public function testFilter(ImageExtension $imageExtension, string $expected): void
    {
        $actual = $imageExtension->getFilter();
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getImageTypes')]
    public function testImageType(ImageExtension $extension, int $expected): void
    {
        $actual = $extension->getImageType();
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param array{
     *      compressed?: bool,
     *      quality?: int,
     *      filters?: int,
     *      foreground_color?: int|null} $options
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getInvalidOptions')]
    public function testInvalidOptions(ImageExtension $extension, array $options): void
    {
        self::expectException(\RuntimeException::class);
        $image = \imagecreatetruecolor(100, 100);
        self::assertInstanceOf(\GdImage::class, $image);

        try {
            $extension->saveImage(image: $image, options: $options);
            self::fail('No exception throw');
        } finally {
            \imagedestroy($image);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFromTypes')]
    public function testTryFromType(int $type, ImageExtension $expected = null): void
    {
        $actual = ImageExtension::tryFromType($type);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTypes')]
    public function testType(ImageExtension $extension, int $expected): void
    {
        $actual = $extension->getImageType();
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param array{
     *       compressed?: bool,
     *       quality?: int,
     *       filters?: int,
     *       foreground_color?: int|null} $options
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getValidOptions')]
    public function testValidOptions(ImageExtension $extension, array $options, bool $expected = true): void
    {
        $image = \imagecreatetruecolor(100, 100);
        self::assertInstanceOf(\GdImage::class, $image);

        $file = FileUtils::tempFile();
        self::assertIsString($file);

        try {
            $result = $extension->saveImage($image, $file, $options);
            self::assertSame($expected, $result);
        } finally {
            \imagedestroy($image);
            FileUtils::remove($file);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValues(ImageExtension $imageExtension, string $expected): void
    {
        $actual = $imageExtension->value;
        self::assertSame($expected, $actual);
    }
}
