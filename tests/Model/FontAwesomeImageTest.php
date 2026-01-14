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

namespace App\Tests\Model;

use App\Model\FontAwesomeImage;
use App\Model\ImageSize;
use PHPUnit\Framework\TestCase;

final class FontAwesomeImageTest extends TestCase
{
    public static function assertSameImageSize(int $expectedWidth, int $expectedHeight, ImageSize $actual): void
    {
        self::assertSame($expectedWidth, $actual->width);
        self::assertSame($expectedHeight, $actual->height);
    }

    public function testConstructor(): void
    {
        $content = 'My Content';
        $width = 140;
        $height = 220;
        $resolution = 76;
        $mimeType = 'image/png';
        $image = $this->createImage($content, $width, $height, $resolution);
        self::assertSame($content, $image->getContent());
        self::assertSameImageSize($width, $height, $image->getSize());
        self::assertSame($resolution, $image->getResolution());
        self::assertSame($mimeType, $image->getMimeType());
    }

    public function testResizeBiggestHeight(): void
    {
        $image = $this->createImage(width: 20, height: 40);
        $actual = $image->resize(80);
        self::assertSameImageSize(40, 80, $actual);
    }

    public function testResizeBiggestWidth(): void
    {
        $image = $this->createImage(width: 40, height: 20);
        $actual = $image->resize(80);
        self::assertSameImageSize(80, 40, $actual);
    }

    public function testResizeSameValues(): void
    {
        $image = $this->createImage(width: 40, height: 40);
        $actual = $image->resize(50);
        self::assertSameImageSize(50, 50, $actual);
    }

    private function createImage(
        string $content = 'content',
        int $width = 30,
        int $height = 50,
        int $resolution = 96
    ): FontAwesomeImage {
        return new FontAwesomeImage($content, ImageSize::instance($width, $height), $resolution);
    }
}
