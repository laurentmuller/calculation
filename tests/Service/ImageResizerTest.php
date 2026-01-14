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

use App\Service\ImageResizer;
use App\Tests\TranslatorMockTrait;
use App\Utils\FileUtils;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ImageResizerTest extends TestCase
{
    use TranslatorMockTrait;

    #[\Override]
    protected function tearDown(): void
    {
        FileUtils::remove($this->getTarget());
    }

    public function testResize(): void
    {
        $source = $this->getSource();
        $target = $this->getTarget();
        $service = $this->createService();
        $actual = $service->resize($source, $target);
        self::assertTrue($actual);
        $this->assertImageValid($target, 192);
    }

    public function testResizeHeightGreaterWidth(): void
    {
        $source = __DIR__ . '/../files/images/example.png';
        $target = $this->getTarget();
        $service = $this->createService();
        $actual = $service->resize($source, $target);
        self::assertTrue($actual);
        $this->assertImageValid($target, 162, 192);
    }

    public function testResizeInvalidFile(): void
    {
        $service = $this->createService();
        $actual = $service->resize(__FILE__, $this->getTarget());
        self::assertFalse($actual);
    }

    public function testResizeWidthGreaterHeight(): void
    {
        $source = __DIR__ . '/../files/images/example.jpeg';
        $target = $this->getTarget();
        $service = $this->createService();
        $actual = $service->resize($source, $target);
        self::assertTrue($actual);
        $this->assertImageValid($target, 192, 108);
    }

    private function assertImageValid(string $target, int $width, ?int $height = null): void
    {
        $image = \imagecreatefrompng($target);
        self::assertInstanceOf(\GdImage::class, $image);
        self::assertSame($width, \imagesx($image));
        self::assertSame($height ?? $width, \imagesy($image));
        \imagedestroy($image);
    }

    private function createService(): ImageResizer
    {
        $translator = $this->createMockTranslator();
        $logger = $this->createMock(LoggerInterface::class);

        return new ImageResizer($translator, $logger);
    }

    private function getSource(): string
    {
        return __DIR__ . '/../files/images/example.bmp';
    }

    private function getTarget(): string
    {
        return __DIR__ . '/target.png';
    }
}
