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

use App\Enums\FontAwesomePath;
use App\Model\FontAwesomeIcon;
use App\Model\FontAwesomeImage;
use App\Model\ImageSize;
use App\Pdf\PdfFontAwesomeCell;
use App\Service\FontAwesomeCellService;
use App\Service\FontAwesomeImageService;
use PHPUnit\Framework\TestCase;

final class FontAwesomeCellServiceTest extends TestCase
{
    public function testImageNull(): void
    {
        $imageService = $this->createImageService(null);
        $cellService = $this->createCellService($imageService);
        $icon = $this->createIcon();
        $actual = $cellService->getCell($icon);
        self::assertNull($actual);
    }

    public function testImageValid(): void
    {
        $image = new FontAwesomeImage('content', ImageSize::instance(100, 100), 96);
        $imageService = $this->createImageService($image);
        $cellService = $this->createCellService($imageService);
        $icon = $this->createIcon();
        $actual = $cellService->getCell($icon);
        self::assertInstanceOf(PdfFontAwesomeCell::class, $actual);
    }

    private function createCellService(FontAwesomeImageService $imageService): FontAwesomeCellService
    {
        return new FontAwesomeCellService($imageService);
    }

    private function createIcon(): FontAwesomeIcon
    {
        return new FontAwesomeIcon(FontAwesomePath::SOLID, 'fake');
    }

    private function createImageService(?FontAwesomeImage $image): FontAwesomeImageService
    {
        $imageService = self::createStub(FontAwesomeImageService::class);
        $imageService->method('getImage')
            ->willReturn($image);

        return $imageService;
    }
}
