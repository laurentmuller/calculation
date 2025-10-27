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
use App\Report\FontAwesomeReport;
use App\Service\FontAwesomeImageService;
use App\Utils\FileUtils;
use fpdf\PdfException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FontAwesomeReportTest extends TestCase
{
    public function testRenderWithException(): void
    {
        self::expectException(PdfException::class);
        $report = $this->createReport(null);
        $report->render();
    }

    public function testRenderWithSuccess(): void
    {
        $image = $this->createImage();
        $report = $this->createReport($image);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createImage(): FontAwesomeImage
    {
        $file = $this->getSvgDirectory() . '/example.png';
        $content = FileUtils::readFile($file);

        return new FontAwesomeImage($content, 124, 147, 96);
    }

    private function createReport(?FontAwesomeImage $image): FontAwesomeReport
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createService($image);

        return new FontAwesomeReport($controller, $service);
    }

    private function createService(?FontAwesomeImage $image): MockObject&FontAwesomeImageService
    {
        $svgDirectory = $this->getSvgDirectory();
        $service = $this->createMock(FontAwesomeImageService::class);
        $service->method('getSvgDirectory')
            ->willReturn($svgDirectory);
        $service->method('getImage')
            ->willReturn($image);

        return $service;
    }

    private function getSvgDirectory(): string
    {
        return __DIR__ . '/../files/images';
    }
}
