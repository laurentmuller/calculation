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

namespace App\Tests\Service;

use App\Service\ImageService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

class ImageServiceTest extends TestCase
{
    public function testAllocate(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        self::assertIsInt($service->allocate(255, 255, 255));
    }

    public function testAllocateAlpha(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        self::assertIsInt($service->allocateAlpha(255, 255, 255));
        self::assertIsInt($service->allocateAlpha(255, 255, 255, 100));
    }

    public function testAllocateColors(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        self::assertIsInt($service->allocateBlack());
        self::assertIsInt($service->allocateWhite());
    }

    public function testAlphaBlending(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        self::assertTrue($service->alphaBlending(true));
        self::assertTrue($service->alphaBlending(false));
    }

    public function testCopyResampled(): void
    {
        $source = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($source);
        $target = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($target);
        self::assertTrue($source->copyResampled($target, 0, 0, 0, 0, 10, 10, 10, 10));
    }

    public function testFill(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $black = $service->allocateBlack();
        self::assertIsInt($black);
        self::assertTrue($service->fill($black, 10, 15));
    }

    public function testFillRectangle(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $black = $service->allocateBlack();
        self::assertIsInt($black);
        self::assertTrue($service->fillRectangle($black, 0, 0, 14, 22));
    }

    public function testFromFile(): void
    {
        $file = __DIR__ . '/../Data/images/example.png';
        $service = ImageService::fromFile($file);
        self::assertNotNull($service);
        self::assertSame($file, $service->getFilename());
        $image = $service->getImage();
        $x = \imagesx($image);
        self::assertSame(124, $x);
        $y = \imagesy($image);
        self::assertSame(147, $y);
    }

    public function testFromFileInvalid(): void
    {
        $file = Path::normalize(__FILE__);
        $service = ImageService::fromFile($file);
        self::assertNull($service);

        $file = Path::normalize(__DIR__ . '/../Data/images/example_invalid.png');
        $service = ImageService::fromFile($file);
        self::assertNull($service);
    }

    public function testFromTrueColor(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        self::assertNull($service->getFilename());
        $image = $service->getImage();
        $x = \imagesx($image);
        self::assertSame(100, $x);
        $y = \imagesy($image);
        self::assertSame(200, $y);
    }

    public function testLine(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $black = $service->allocateBlack();
        self::assertIsInt($black);
        self::assertTrue($service->line(0, 0, 10, 15, $black));
    }

    public function testRectangle(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $black = $service->allocateBlack();
        self::assertIsInt($black);
        self::assertTrue($service->rectangle(0, 0, 10, 15, $black));
    }

    public function testResolution(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $actual = $service->resolution();
        self::assertSame(96, $actual);
    }

    public function testSaveAlpha(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        self::assertTrue($service->saveAlpha(true));
        self::assertTrue($service->saveAlpha(false));
    }

    public function testSetPixel(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $black = $service->allocateBlack();
        self::assertIsInt($black);
        self::assertTrue($service->setPixel(1, 1, $black));
    }

    public function testTransparent(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $black = $service->allocateBlack();
        self::assertIsInt($black);
        $actual = $service->transparent($black);
        self::assertSame(0, $actual);
    }

    public function testTtfBox(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $font = $this->getFont();
        $actual = $service->ttfBox(10.0, 0.0, $font, 'text');
        self::assertIsArray($actual);
    }

    public function testTtfSize(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $font = $this->getFont();
        $actual = $service->ttfSize(10.0, 0.0, $font, 'text');
        self::assertNotEmpty($actual);
        self::assertCount(2, $actual);
    }

    public function testTtfSizeInvalid(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $actual = $service->ttfSize(10.0, 0.0, Path::normalize(__FILE__), 'text');
        self::assertNotEmpty($actual);
        self::assertCount(2, $actual);
        self::assertSame(0, $actual[0]);
        self::assertSame(0, $actual[1]);
    }

    public function testTtfText(): void
    {
        $service = ImageService::fromTrueColor(100, 200);
        self::assertNotNull($service);
        $black = $service->allocateBlack();
        self::assertIsInt($black);

        $font = $this->getFont();
        $actual = $service->ttfText(10.0, 0.0, 0, 0, $black, $font, 'text');
        self::assertIsArray($actual);
    }

    private function getFont(): string
    {
        return Path::normalize(__DIR__ . '/../../resources/fonts/captcha.ttf');
    }
}
