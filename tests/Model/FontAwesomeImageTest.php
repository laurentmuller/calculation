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
use PHPUnit\Framework\TestCase;

class FontAwesomeImageTest extends TestCase
{
    public function testConstructor(): void
    {
        $content = 'My Content';
        $width = 140;
        $height = 220;
        $resolution = 76;
        $mimeType = 'image/png';
        $actual = $this->createImage($content, $width, $height, $resolution);
        self::assertSame($content, $actual->getContent());
        self::assertSame($width, $actual->getWidth());
        self::assertSame($height, $actual->getHeight());
        self::assertSame($resolution, $actual->getResolution());
        self::assertSame($mimeType, $actual->getMimeType());
    }

    public function testResizeBiggestHeight(): void
    {
        $image = $this->createImage(width: 20, height: 40);
        $actual = $image->resize(80);
        self::assertSame([40, 80], $actual);
    }

    public function testResizeBiggestWidth(): void
    {
        $image = $this->createImage(width: 40, height: 20);
        $actual = $image->resize(80);
        self::assertSame([80, 40], $actual);
    }

    public function testResizeSameValues(): void
    {
        $image = $this->createImage(width: 40, height: 40);
        $actual = $image->resize(50);
        self::assertSame([50, 50], $actual);
    }

    private function createImage(
        string $content = 'content',
        int $width = 30,
        int $height = 50,
        int $resolution = 96
    ): FontAwesomeImage {
        return new FontAwesomeImage($content, $width, $height, $resolution);
    }
}
