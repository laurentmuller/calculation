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
use App\Service\FontAwesomeService;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;

class MemoryImageReportTest extends TestCase
{
    public function testEmptyImage(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $image = __DIR__ . '/../files/txt/empty.txt';
        $report = new MemoryImageReport($controller, $image);
        $report->render();
    }

    public function testInvalidImage(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller, __FILE__);
        $report->render();
    }

    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $image = $this->getTestFile();
        $report = new MemoryImageReport($controller, $image);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderWithIconFile(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $iconFile = $this->getImageFile('icons/favicon-114x114.png');
        $report = new MemoryImageReport($controller, iconFile: $iconFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderWithInvalidIconFile(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller, iconFile: 'fake');
        $report->render();
    }

    public function testRenderWithLogoFile(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logoFile = $this->getImageFile('icons/favicon-114x114.png');
        $report = new MemoryImageReport($controller, logoFile: $logoFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderWithScreenshot(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $screenshotFile = $this->getImageFile('screenshots/home_light.png');
        $report = new MemoryImageReport($controller, screenshotFile: $screenshotFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderWithService(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $image = $this->getImage();
        $service = $this->createMock(FontAwesomeService::class);
        $service->method('getImage')
            ->willReturn($image);
        $report = new MemoryImageReport(controller: $controller, service: $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithAllImages(): void
    {
        $logoFile = $this->getImageFile('logo/logo-customer-148x148.png');
        $iconFile = $this->getImageFile('icons/favicon-144x144.png');
        $screenshotFile = $this->getImageFile('screenshots/home_light.png');
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport(
            controller: $controller,
            logoFile: $logoFile,
            iconFile: $iconFile,
            screenshotFile: $screenshotFile,
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithNoArgument(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function getImage(): FontAwesomeImage
    {
        $path = $this->getTestFile();
        $content = \file_get_contents($path);
        self::assertIsString($content);

        return new FontAwesomeImage($content, 64, 64, 96);
    }

    private function getImageFile(string $name): string
    {
        $file = __DIR__ . '/../../public/images/' . $name;
        self::assertFileExists($file);

        return $file;
    }

    private function getTestFile(): string
    {
        $file = __DIR__ . '/../files/images/example.png';
        self::assertFileExists($file);

        return $file;
    }
}
