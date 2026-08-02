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

use App\Interfaces\DocumentHelperInterface;
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
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            logoFile: __DIR__ . '/../files/txt/empty.txt'
        );
        $report->render();
    }

    public function testImageInvalid(): void
    {
        self::expectException(PdfException::class);
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            logoFile: __FILE__
        );
        $report->render();
    }

    public function testRender(): void
    {
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            logoFile: $this->getTestFile()
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderIconFile(): void
    {
        $iconFile = $this->getImageFile('icons/favicon-114x114.png');
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            iconFile: $iconFile
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderIconFileInvalid(): void
    {
        self::expectException(PdfException::class);
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            iconFile: 'fake'
        );
        $report->render();
    }

    public function testRenderLogoFile(): void
    {
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            logoFile: $this->getImageFile('icons/favicon-114x114.png')
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderScreenshot(): void
    {
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            screenshotFile: $this->getImageFile('screenshots/home_light.png')
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderService(): void
    {
        $image = $this->getImage();
        $service = $this->createMock(FontAwesomeService::class);
        $service->method('getFontAwesomeImage')
            ->willReturn($image);
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            service: $service
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderServiceWithoutImage(): void
    {
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            service: self::createStub(FontAwesomeService::class)
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderServiceWithoutPath(): void
    {
        $image = $this->getImage();
        $service = $this->createMock(FontAwesomeService::class);
        $service->method('getFontAwesomeImage')
            ->willReturn($image);
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            service: $service
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderTransparencyImage(): void
    {
        $transparencyFile = $this->getImageFile('icons/favicon-114x114.png');
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            transparencyFile: $transparencyFile
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderTransparencyImageInvalid(): void
    {
        self::expectException(PdfException::class);
        $transparencyFile = Path::join(__DIR__, 'fake.txt');
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class),
            transparencyFile: $transparencyFile
        );
        $report->render();
    }

    public function testWithAllImages(): void
    {
        $logoFile = $this->getImageFile('logo/customer_148_148.png');
        $iconFile = $this->getImageFile('icons/favicon-144x144.png');
        $screenshotFile = $this->getImageFile('screenshots/home_light.png');
        $helper = self::createStub(DocumentHelperInterface::class);
        $report = new MemoryImageReport(
            helper: $helper,
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
        $report = new MemoryImageReport(
            helper: self::createStub(DocumentHelperInterface::class)
        );
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
        $path = Path::join(__DIR__, '/../../public/images', $name);
        self::assertFileExists($path);

        return $path;
    }

    private function getTestFile(): string
    {
        $file = Path::join(__DIR__, '/../files/images/example.png');
        self::assertFileExists($file);

        return $file;
    }
}
