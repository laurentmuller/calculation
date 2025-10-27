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

use App\Enums\ImageSize;
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

    public function testResizeDefault(): void
    {
        $source = $this->getSource();
        $target = $this->getTarget();
        $service = $this->createService();
        $actual = $service->resizeDefault($source, $target);
        self::assertTrue($actual);
        $this->assertImageValid($target, 192);
    }

    public function testResizeInvalidFile(): void
    {
        $service = $this->createService();
        $actual = $service->resize(__FILE__, $this->getTarget(), ImageSize::DEFAULT);
        self::assertFalse($actual);
    }

    public function testResizeMedium(): void
    {
        $source = $this->getSource();
        $target = $this->getTarget();
        $service = $this->createService();
        $actual = $service->resizeMedium($source, $target);
        self::assertTrue($actual);
        $this->assertImageValid($target, 96);
    }

    public function testResizeSmall(): void
    {
        $source = $this->getSource();
        $target = $this->getTarget();
        $service = $this->createService();
        $actual = $service->resizeSmall($source, $target);
        self::assertTrue($actual);
        $this->assertImageValid($target, 32);
    }

    public function testResizeWidthGreaterHeight(): void
    {
        $source = __DIR__ . '/../files/images/example.png';
        $target = $this->getTarget();
        $service = $this->createService();
        $actual = $service->resizeDefault($source, $target);
        self::assertTrue($actual);
        $this->assertImageValid($target, 161, 192);
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
        $service = new ImageResizer();
        $service->setTranslator($this->createMockTranslator());
        $service->setLogger($this->createMock(LoggerInterface::class));

        return $service;
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
