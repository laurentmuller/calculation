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
    public static function getInvalidOptions(): \Generator
    {
        $values = ImageExtension::cases();
        foreach ($values as $value) {
            yield [$value, ['fake' => 100]];
        }
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
        $default = ImageExtension::getDefault();
        self::assertSame(ImageExtension::PNG, $default);
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
        self::assertSame($expected, $imageExtension->value);
    }
}
