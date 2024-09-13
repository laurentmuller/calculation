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
        $resolution = 128;
        $actual = $this->createImage($content, $width, $height, $resolution);
        self::assertSame($content, $actual->getContent());
        self::assertSame($width, $actual->getWidth());
        self::assertSame($height, $actual->getHeight());
        self::assertSame($resolution, $actual->getResolution());
    }

    public function testResizeFloatBiggestHeight(): void
    {
        $image = $this->createImage(width: 20, height: 40);
        $actual = $image->resize(80.0);
        self::assertSame([40.0, 80.0], $actual);
    }

    public function testResizeFloatBiggestWidth(): void
    {
        $image = $this->createImage(width: 40, height: 20);
        $actual = $image->resize(80.0);
        self::assertSame([80.0, 40.0], $actual);
    }

    public function testResizeFloatSameValues(): void
    {
        $image = $this->createImage(width: 40, height: 40);
        $actual = $image->resize(50.0);
        self::assertSame([50.0, 50.0], $actual);
    }

    public function testResizeIntBiggestHeight(): void
    {
        $image = $this->createImage(width: 20, height: 40);
        $actual = $image->resize(80);
        self::assertSame([40, 80], $actual);
    }

    public function testResizeIntBiggestWidth(): void
    {
        $image = $this->createImage(width: 40, height: 20);
        $actual = $image->resize(80);
        self::assertSame([80, 40], $actual);
    }

    public function testResizeIntSameValues(): void
    {
        $image = $this->createImage(width: 40, height: 40);
        $actual = $image->resize(50);
        self::assertSame([50, 50], $actual);
    }

    private function createImage(
        string $content = 'content',
        int $width = 10,
        int $height = 30,
        int $resolution = 96
    ): FontAwesomeImage {
        return new FontAwesomeImage($content, $width, $height, $resolution);
    }
}
