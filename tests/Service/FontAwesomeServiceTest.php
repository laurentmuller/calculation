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

use App\Model\FontAwesomeImage;
use App\Pdf\PdfFontAwesomeCell;
use App\Service\FontAwesomeIconService;
use App\Service\FontAwesomeImageService;
use App\Service\FontAwesomeService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FontAwesomeServiceTest extends TestCase
{
    public function testCellImageNull(): void
    {
        $imageService = $this->createMock(FontAwesomeImageService::class);
        $imageService->method('getImage')
            ->willReturn(null);

        $iconService = $this->createMock(FontAwesomeIconService::class);
        $iconService->method('getPath')
            ->willReturn('/');

        $service = $this->createService($imageService, $iconService);
        $actual = $service->getFontAwesomeCell('fa-solid');
        self::assertNull($actual);
    }

    public function testCellNotNull(): void
    {
        $image = $this->createMock(FontAwesomeImage::class);

        $imageService = $this->createMock(FontAwesomeImageService::class);
        $imageService->method('getImage')
            ->willReturn($image);

        $iconService = $this->createMock(FontAwesomeIconService::class);
        $iconService->method('getPath')
            ->willReturn('/');

        $service = $this->createService($imageService, $iconService);
        $actual = $service->getFontAwesomeCell('fa-solid');
        self::assertInstanceOf(PdfFontAwesomeCell::class, $actual);
    }

    public function testCellNull(): void
    {
        $service = $this->createService();
        $actual = $service->getFontAwesomeCell('fa-solid');
        self::assertNull($actual);
    }

    public function testImage(): void
    {
        $service = $this->createService();
        $actual = $service->getImage('/');
        self::assertNull($actual);
    }

    public function testPath(): void
    {
        $service = $this->createService();
        $actual = $service->getPath('fa-solid');
        self::assertNull($actual);
    }

    private function createService(
        (MockObject&FontAwesomeImageService)|null $imageService = null,
        (MockObject&FontAwesomeIconService)|null $iconService = null
    ): FontAwesomeService {
        $imageService ??= $this->createMock(FontAwesomeImageService::class);
        $iconService ??= $this->createMock(FontAwesomeIconService::class);

        return new FontAwesomeService($imageService, $iconService);
    }
}
