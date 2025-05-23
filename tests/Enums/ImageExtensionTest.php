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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type SaveOptionsType from ImageExtension
 */
class ImageExtensionTest extends TestCase
{
    /**
     * @phpstan-return \Generator<int, array{ImageExtension, string}>
     */
    public static function getCreateImages(): \Generator
    {
        /** @phpstan-var non-empty-string $dir */
        $dir = \realpath(__DIR__ . '/../files/images');
        yield [ImageExtension::BMP, $dir . '/example.bmp'];
        yield [ImageExtension::GIF, $dir . '/example.gif'];
        yield [ImageExtension::JPEG, $dir . '/example.jpeg'];
        yield [ImageExtension::JPG, $dir . '/example.jpg'];
        yield [ImageExtension::PNG, $dir . '/example.png'];
        yield [ImageExtension::WEBP, $dir . '/example.webp'];
        yield [ImageExtension::XBM, $dir . '/example.xbm'];
        yield [ImageExtension::XPM, $dir . '/example.xpm'];
    }

    /**
     * @phpstan-return \Generator<int, array{ImageExtension, string}>
     */
    public static function getFilters(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{ImageExtension, int}>
     */
    public static function getImageTypes(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{ImageExtension, SaveOptionsType}>
     */
    public static function getInvalidOptions(): \Generator
    {
        /**
         * @phpstan-var SaveOptionsType $options
         *
         * @phpstan-ignore varTag.nativeType
         */
        $options = ['fake' => 100];
        $values = ImageExtension::cases();
        foreach ($values as $value) {
            yield [$value, $options];
        }
    }

    /**
     * @phpstan-return \Generator<int, array{0: int, 1?: ImageExtension}>
     */
    public static function getTryFromTypes(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{ImageExtension, int}>
     */
    public static function getTypes(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{0: ImageExtension, 1: SaveOptionsType, 2?: false}>
     */
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

    /**
     * @phpstan-return \Generator<int, array{ImageExtension, string}>
     */
    public static function getValues(): \Generator
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
        self::assertCount(9, ImageExtension::cases());
    }

    #[DataProvider('getCreateImages')]
    public function testCreateImage(ImageExtension $extension, string $filename): void
    {
        if (!\file_exists($filename)) {
            self::markTestSkipped("Unable to find the image file: $filename.");
        }

        $image = $extension->createImage($filename);
        self::assertInstanceOf(\GdImage::class, $image);
        \imagedestroy($image);
    }

    public function testDefault(): void
    {
        $expected = ImageExtension::PNG;
        $actual = ImageExtension::getDefault();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getFilters')]
    public function testFilter(ImageExtension $imageExtension, string $expected): void
    {
        $actual = $imageExtension->getFilter();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getImageTypes')]
    public function testImageType(ImageExtension $extension, int $expected): void
    {
        $actual = $extension->getImageType();
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param SaveOptionsType $options
     */
    #[DataProvider('getInvalidOptions')]
    public function testInvalidOptions(ImageExtension $extension, array $options): void
    {
        self::expectException(\InvalidArgumentException::class);
        $image = \imagecreatetruecolor(100, 100);
        self::assertInstanceOf(\GdImage::class, $image);

        try {
            $extension->saveImage(image: $image, options: $options);
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

    #[DataProvider('getTryFromTypes')]
    public function testTryFromType(int $type, ?ImageExtension $expected = null): void
    {
        $actual = ImageExtension::tryFromType($type);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getTypes')]
    public function testType(ImageExtension $extension, int $expected): void
    {
        $actual = $extension->getImageType();
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param SaveOptionsType $options
     */
    #[DataProvider('getValidOptions')]
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

    #[DataProvider('getValues')]
    public function testValues(ImageExtension $imageExtension, string $expected): void
    {
        $actual = $imageExtension->value;
        self::assertSame($expected, $actual);
    }
}
