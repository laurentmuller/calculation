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
use App\Utils\FileUtils;
use PHPUnit\Framework\TestCase;

final class ImageServiceTest extends TestCase
{
    public function testAllocate(): void
    {
        $service = $this->createService();
        self::assertNonNegative($service->allocateBlack());
        self::assertNonNegative($service->allocateWhite());
        self::assertNonNegative($service->allocate(255, 255, 255));
    }

    public function testFill(): void
    {
        $service = $this->createService();
        $black = $service->allocateBlack();
        self::assertTrue($service->fill($black, 10, 15));
    }

    public function testFillRectangle(): void
    {
        $service = $this->createService();
        $black = $service->allocateBlack();
        self::assertTrue($service->fillRectangle($black, 0, 0, 14, 22));
    }

    public function testFromFile(): void
    {
        $file = FileUtils::normalize(__DIR__ . '/../files/images/example.png');
        $service = ImageService::fromFile($file);
        self::assertSame($file, $service->getFilename());
    }

    public function testFromFileInvalidImage(): void
    {
        $file = FileUtils::normalize(__DIR__ . '/../files/images/example_invalid.png');
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Unable to load image from "' . $file . '".');
        @ImageService::fromFile($file);
    }

    public function testFromFileInvalidName(): void
    {
        $file = FileUtils::normalize(__FILE__);
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Unsupported file image extension "' . $file . '".');
        ImageService::fromFile($file);
    }

    public function testFromTrueColor(): void
    {
        $service = $this->createService();
        self::assertNull($service->getFilename());
        $image = $service->getImage();
        $x = \imagesx($image);
        self::assertSame(100, $x);
        $y = \imagesy($image);
        self::assertSame(200, $y);
    }

    public function testLine(): void
    {
        $service = $this->createService();
        $black = $service->allocateBlack();
        self::assertTrue($service->line(0, 0, 10, 15, $black));
    }

    public function testRectangle(): void
    {
        $service = $this->createService();
        $black = $service->allocateBlack();
        self::assertTrue($service->rectangle(0, 0, 10, 15, $black));
    }

    public function testSetPixel(): void
    {
        $service = $this->createService();
        $black = $service->allocateBlack();
        self::assertTrue($service->setPixel(1, 1, $black));
    }

    public function testTtfBox(): void
    {
        $service = $this->createService();
        $font = $this->getFont();
        $actual = $service->ttfBox(10.0, 0.0, $font, 'text');
        self::assertIsArray($actual);
    }

    public function testTtfSize(): void
    {
        $service = $this->createService();
        $font = $this->getFont();
        $actual = $service->ttfSize(10.0, 0.0, $font, 'text');
        self::assertNotEmpty($actual);
        self::assertCount(2, $actual);
    }

    public function testTtfSizeInvalid(): void
    {
        $service = $this->createService();
        $actual = @$service->ttfSize(10.0, 0.0, FileUtils::normalize(__FILE__), 'text');
        self::assertNotEmpty($actual);
        self::assertCount(2, $actual);
        self::assertSame(0, $actual[0]);
        self::assertSame(0, $actual[1]);
    }

    public function testTtfText(): void
    {
        $service = $this->createService();
        $black = $service->allocateBlack();
        $font = $this->getFont();
        $actual = $service->ttfText(10.0, 0.0, 0, 0, $black, $font, 'text');
        self::assertIsArray($actual);
    }

    protected static function assertNonNegative(int $value): void
    {
        self::assertGreaterThanOrEqual(0, $value);
    }

    private function createService(): ImageService
    {
        return ImageService::fromTrueColor(100, 200);
    }

    private function getFont(): string
    {
        return FileUtils::normalize(__DIR__ . '/../../resources/fonts/captcha.ttf');
    }
}
