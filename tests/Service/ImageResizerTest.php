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
use App\Tests\KernelServiceTestCase;
use App\Utils\FileUtils;

class ImageResizerTest extends KernelServiceTestCase
{
    private ImageResizer $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(ImageResizer::class);
    }

    public function testResizeDefault(): void
    {
        $source = $this->getSource();
        $target = $this->getTarget();
        $actual = $this->service->resizeDefault($source, $target);
        self::assertTrue($actual);
        self::assertImageValid($target, 192);
    }

    public function testResizeInvalidFile(): void
    {
        $actual = $this->service->resize(__FILE__, $this->getTarget(), ImageSize::DEFAULT);
        self::assertFalse($actual);
    }

    public function testResizeMedium(): void
    {
        $source = $this->getSource();
        $target = $this->getTarget();
        $actual = $this->service->resizeMedium($source, $target);
        self::assertTrue($actual);
        self::assertImageValid($target, 96);
    }

    public function testResizeSmall(): void
    {
        $source = $this->getSource();
        $target = $this->getTarget();
        $actual = $this->service->resizeSmall($source, $target);
        self::assertTrue($actual);
        self::assertImageValid($target, 32);
    }

    public function testResizeWidthGreaterHeight(): void
    {
        $source = __DIR__ . '/../Data/images/example.png';
        $target = $this->getTarget();
        $actual = $this->service->resizeDefault($source, $target);
        self::assertTrue($actual);
        self::assertImageValid($target, 161, 192);
    }

    protected static function assertImageValid(string $target, int $width, ?int $height = null): void
    {
        try {
            $image = \imagecreatefrompng($target);
            self::assertInstanceOf(\GdImage::class, $image);
            self::assertSame($width, \imagesx($image));
            self::assertSame($height ?? $width, \imagesy($image));
            \imagedestroy($image);
        } finally {
            FileUtils::remove($target);
        }
    }

    private function getSource(): string
    {
        return __DIR__ . '/../Data/images/example.bmp';
    }

    private function getTarget(): string
    {
        return __DIR__ . '/target.png';
    }
}
