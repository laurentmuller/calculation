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
use App\Service\ImageService;
use App\Utils\FileUtils;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ImageExtension::class)]
class ImageExtensionTest extends TestCase
{
    public static function getFilters(): \Iterator
    {
        yield [ImageExtension::BMP, '*.bmp'];
        yield [ImageExtension::GIF, '*.gif'];
        yield [ImageExtension::JPEG, '*.jpeg'];
        yield [ImageExtension::JPG, '*.jpg'];
        yield [ImageExtension::PNG, '*.png'];
        yield [ImageExtension::WBMP, '*.wbmp'];
        yield [ImageExtension::WEBP, '*.webp'];
        yield [ImageExtension::XBM, '*.xbm'];
        yield [ImageExtension::XPM, '*.xpm'];
    }

    public static function getImages(): \Iterator
    {
        $dir = __DIR__ . '../../Data/Images/';
        yield [ImageExtension::BMP, $dir . 'example.bmp'];
        yield [ImageExtension::GIF, $dir . 'example.gif'];
        yield [ImageExtension::JPEG, $dir . 'example.jpeg'];
        yield [ImageExtension::JPG, $dir . 'example.jpg'];
        yield [ImageExtension::PNG, $dir . 'example.png'];
        yield [ImageExtension::WEBP, $dir . 'example.webp'];

        yield [ImageExtension::BMP, __DIR__ . 'fake.bmp', false];
    }

    public static function getImageTypes(): \Iterator
    {
        yield [ImageExtension::BMP,  \IMAGETYPE_BMP];
        yield [ImageExtension::GIF, \IMAGETYPE_GIF];
        yield [ImageExtension::JPEG, \IMAGETYPE_JPEG];
        yield [ImageExtension::JPG, \IMAGETYPE_JPEG];
        yield [ImageExtension::PNG, \IMAGETYPE_PNG];
        yield [ImageExtension::WBMP,  \IMAGETYPE_WBMP];
        yield [ImageExtension::WEBP,  \IMAGETYPE_WEBP];
        yield [ImageExtension::XBM,  \IMAGETYPE_XBM];
        yield [ImageExtension::XPM,  \IMAGETYPE_UNKNOWN];
    }

    public static function getInvalidOptions(): \Generator
    {
        $values = ImageExtension::cases();
        foreach ($values as $value) {
            yield [$value, ['fake' => 100]];
        }
    }

    public static function getTryFromTypes(): \Iterator
    {
        yield [\IMAGETYPE_BMP, ImageExtension::BMP];
        yield [\IMAGETYPE_GIF, ImageExtension::GIF];
        yield [\IMAGETYPE_JPEG, ImageExtension::JPEG];
        yield [\IMAGETYPE_PNG, ImageExtension::PNG];
        yield [\IMAGETYPE_WBMP, ImageExtension::WBMP];
        yield [\IMAGETYPE_WEBP, ImageExtension::WEBP];
        yield [\IMAGETYPE_XBM, ImageExtension::XBM];
        yield [\IMAGETYPE_UNKNOWN];
        yield [-1];
    }

    public static function getTypes(): \Iterator
    {
        yield [ImageExtension::BMP, \IMAGETYPE_BMP];
        yield [ImageExtension::GIF, \IMAGETYPE_GIF];
        yield [ImageExtension::JPEG, \IMAGETYPE_JPEG];
        yield [ImageExtension::JPG, \IMAGETYPE_JPEG];
        yield [ImageExtension::PNG, \IMAGETYPE_PNG];
        yield [ImageExtension::WBMP, \IMAGETYPE_WBMP];
        yield [ImageExtension::WEBP, \IMAGETYPE_WEBP];
        yield [ImageExtension::XBM, \IMAGETYPE_XBM];
        yield [ImageExtension::XPM, \IMAGETYPE_UNKNOWN];
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

    public static function getValues(): \Iterator
    {
        yield [ImageExtension::BMP, 'bmp'];
        yield [ImageExtension::GIF, 'gif'];
        yield [ImageExtension::JPEG, 'jpeg'];
        yield [ImageExtension::JPG, 'jpg'];
        yield [ImageExtension::PNG, 'png'];
        yield [ImageExtension::WBMP, 'wbmp'];
        yield [ImageExtension::WEBP, 'webp'];
        yield [ImageExtension::XBM, 'xbm'];
        yield [ImageExtension::XPM, 'xpm'];
    }

    public function testCount(): void
    {
        $expected = 9;
        self::assertCount($expected, ImageExtension::cases());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getImages')]
    public function testCreateImage(ImageExtension $extension, string $filename, bool $expectedImage = true): void
    {
        $image = $extension->createImage($filename);
        if ($expectedImage) {
            self::assertInstanceOf(\GdImage::class, $image);
            \imagedestroy($image);
        } else {
            self::assertFalse($image);
        }
    }

    public function testDefault(): void
    {
        $expected = ImageExtension::PNG;
        $actual = ImageExtension::getDefault();
        self::assertSame($expected, $actual);
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

    public function testSaveImageService(): void
    {
        $file = FileUtils::tempFile();
        self::assertIsString($file);

        try {
            $service = ImageService::fromTrueColor(50, 50);
            self::assertNotNull($service);
            $extension = ImageExtension::PNG;
            $result = $extension->saveImage($service, $file);
            self::assertTrue($result);
        } finally {
            FileUtils::remove($file);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFromTypes')]
    public function testTryFromType(int $type, ?ImageExtension $expected = null): void
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
