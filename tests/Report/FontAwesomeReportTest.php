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

use App\Enums\FontAwesomePath;
use App\Interfaces\DocumentHelperInterface;
use App\Model\FontAwesomeImage;
use App\Model\ImageSize;
use App\Report\FontAwesomeReport;
use App\Service\FontAwesomeImageService;
use PHPUnit\Framework\TestCase;

final class FontAwesomeReportTest extends TestCase
{
    private array $directories = [];

    #[\Override]
    protected function setUp(): void
    {
        foreach (FontAwesomePath::cases() as $fontAwesomePath) {
            $path = $fontAwesomePath->getPath();
            if (!\is_dir($path) && \mkdir($path, 0o777, true)) {
                $this->directories[] = $path;
            }
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->directories as $directory) {
            \rmdir($directory);
        }
    }

    public function testRender(): void
    {
        $image = $this->createImage();
        $service = self::createStub(FontAwesomeImageService::class);
        $service->method('getImage')
            ->willReturn($image);
        $helper = self::createStub(DocumentHelperInterface::class);

        $report = new FontAwesomeReport($helper, $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createImage(): FontAwesomeImage
    {
        return new FontAwesomeImage(
            content: $this->loadContent(),
            size: new ImageSize(124, 147),
            resolution: 96
        );
    }

    private function loadContent(): string
    {
        /** @phpstan-var string */
        return \file_get_contents(__DIR__ . '/../files/images/example.png');
    }
}
