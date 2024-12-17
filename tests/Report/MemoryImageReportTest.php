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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Model\FontAwesomeImage;
use App\Report\MemoryImageReport;
use App\Service\FontAwesomeImageService;
use fpdf\PdfException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class MemoryImageReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEmptyImage(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $image = __DIR__ . '/../files/txt/empty.txt';
        $report = new MemoryImageReport($controller, $image);
        $report->render();
    }

    /**
     * @throws Exception
     */
    public function testInvalidImage(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller, __FILE__);
        $report->render();
    }

    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $image = __DIR__ . '/../files/images/example.png';
        $report = new MemoryImageReport($controller, $image);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithIconFile(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $iconFile = __DIR__ . '/../../public/images/icons/favicon-114x114.png';
        $report = new MemoryImageReport($controller, iconFile: $iconFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithInvalidIconFile(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller, iconFile: 'fake');
        $report->render();
    }

    /**
     * @throws Exception
     */
    public function testRenderWithLogoFile(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logoFile = __DIR__ . '/../../public/images/icons/favicon-114x114.png';
        $report = new MemoryImageReport($controller, logoFile: $logoFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithScreenshot(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $screenshotFile = __DIR__ . '/../../public/images/screenshots/home_light.png';
        $report = new MemoryImageReport($controller, screenshotFile: $screenshotFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithService(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $image = $this->getImage();
        $service = $this->createMock(FontAwesomeImageService::class);
        $service->method('getImage')
            ->willReturn($image);
        $report = new MemoryImageReport($controller, imageService: $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testWithNoArgument(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function getImage(): FontAwesomeImage
    {
        $path = __DIR__ . '/../files/images/example.png';
        $content = \file_get_contents($path);
        self::assertIsString($content);

        return new FontAwesomeImage($content, 64, 64, 96);
    }
}
