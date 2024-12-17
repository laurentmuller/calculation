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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class FontAwesomeReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testIsException(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(FontAwesomeImageService::class);
        $service->method('isSvgSupported')
            ->willReturn(false);
        $service->method('isImagickException')
            ->willReturn(true);
        $service->method('getSvgDirectory')
            ->willReturn(__DIR__);

        $report = new FontAwesomeReport($controller, $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderAliases(): void
    {
        $image = $this->createImage();
        $svgDirectory = $this->getSvgDirectory();
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(FontAwesomeImageService::class);
        $service->method('getImage')
            ->willReturn($image);
        $service->method('getSvgDirectory')
            ->willReturn($svgDirectory);
        $service->method('isSvgSupported')
            ->willReturn(true);
        $service->method('isImagickException')
            ->willReturn(false);
        $service->method('getAliases')
            ->willReturn(['key' => 'value']);

        $report = new FontAwesomeReport($controller, $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderImages(): void
    {
        $image = $this->createImage();
        $svgDirectory = $this->getSvgDirectory();
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(FontAwesomeImageService::class);
        $service->method('getImage')
            ->willReturn($image);
        $service->method('getSvgDirectory')
            ->willReturn($svgDirectory);

        $report = new FontAwesomeReport($controller, $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderNoAlias(): void
    {
        $image = $this->createImage();
        $svgDirectory = $this->getSvgDirectory();
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(FontAwesomeImageService::class);
        $service->method('getImage')
            ->willReturn($image);
        $service->method('getSvgDirectory')
            ->willReturn($svgDirectory);
        $service->method('isSvgSupported')
            ->willReturn(true);
        $service->method('isImagickException')
            ->willReturn(false);
        $service->method('getAliases')
            ->willReturn([]);

        $report = new FontAwesomeReport($controller, $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createImage(): FontAwesomeImage
    {
        $path = $this->getSvgDirectory() . '/example.png';
        $content = FileUtils::readFile($path);

        return new FontAwesomeImage($content, 124, 147, 96);
    }

    private function getSvgDirectory(): string
    {
        return __DIR__ . '/../data/images';
    }
}
