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

namespace App\Tests\Traits;

use App\Traits\ImageSizeTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ImageSizeTraitTest extends TestCase
{
    use ImageSizeTrait;

    public static function getSizes(): \Generator
    {
        yield [__DIR__ . '/../files/images/example.png', 124, 147];
        yield [__DIR__ . '/../files/images/example.jpg', 500, 477];
    }

    #[DataProvider('getSizes')]
    public function testImageSize(string $filename, int $width, int $height): void
    {
        $actual = $this->getImageSize($filename);
        self::assertSame($width, $actual->width);
        self::assertSame($height, $actual->height);
    }

    public function testImageSizeFileNotFound(): void
    {
        $filename = __DIR__ . '/__fake__.png';
        $expected = 'The file "' . $filename . '" does not exist.';
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage($expected);
        $this->getImageSize($filename);
    }

    public function testImageSizeNotImage(): void
    {
        $filename = __FILE__;
        $expected = 'Unable to get image size for "' . $filename . '".';
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage($expected);
        $this->getImageSize($filename);
    }
}
