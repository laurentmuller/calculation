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
use App\Model\ImageSize;
use App\Report\MemoryImageReport;
use App\Service\FontAwesomeService;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

final class MemoryImageReportTest extends TestCase
{
    public function testImageEmpty(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $image = __DIR__ . '/../files/txt/empty.txt';
        $report = new MemoryImageReport($controller, $image);
        $report->render();
    }

    public function testImageInvalid(): void
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

    public function testRenderIconFile(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $iconFile = $this->getImageFile('icons/favicon-114x114.png');
        $report = new MemoryImageReport($controller, iconFile: $iconFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderIconFileInvalid(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller, iconFile: 'fake');
        $report->render();
    }

    public function testRenderLogoFile(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logoFile = $this->getImageFile('icons/favicon-114x114.png');
        $report = new MemoryImageReport($controller, logoFile: $logoFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderScreenshot(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $screenshotFile = $this->getImageFile('screenshots/home_light.png');
        $report = new MemoryImageReport($controller, screenshotFile: $screenshotFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderService(): void
    {
        $image = $this->getImage();
        $path = $this->getTestFile();
        $service = $this->createMock(FontAwesomeService::class);
        $service->method('getPath')
            ->willReturn($path);
        $service->method('getImage')
            ->willReturn($image);

        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport(controller: $controller, service: $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderServiceWithoutImage(): void
    {
        $path = $this->getTestFile();
        $service = $this->createMock(FontAwesomeService::class);
        $service->method('getPath')
            ->willReturn($path);

        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport(controller: $controller, service: $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderServiceWithoutPath(): void
    {
        $image = $this->getImage();
        $service = $this->createMock(FontAwesomeService::class);
        $service->method('getImage')
            ->willReturn($image);

        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport(controller: $controller, service: $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderTransparencyImage(): void
    {
        $transparencyFile = $this->getImageFile('icons/favicon-114x114.png');
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport(
            controller: $controller,
            transparencyFile: $transparencyFile
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderTransparencyImageInvalid(): void
    {
        self::expectException(PdfException::class);
        $transparencyFile = Path::join(__DIR__, 'fake.txt');
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport(
            controller: $controller,
            transparencyFile: $transparencyFile
        );
        $report->render();
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
            transparencyFile: $iconFile,
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

        return new FontAwesomeImage($content, ImageSize::instance(64, 64), 96);
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
