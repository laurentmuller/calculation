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

namespace App\Tests\Pdf\Traits;

use App\Controller\AbstractController;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Interfaces\PdfChartInterface;
use App\Pdf\Traits\PdfBarChartTrait;
use App\Report\AbstractReport;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class PdfBarChartTraitTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfBarChartTrait;

            public function render(): bool
            {
                $this->renderBarChart([], []);

                return true;
            }
        };
        $report->addPage();
        $report->resetStyle();
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithRotation(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfBarChartTrait;

            public function render(): bool
            {
                $row = [
                    'label' => \str_repeat('Label', 10),
                    'values' => [
                        [
                            'color' => PdfFillColor::black(),
                            'value' => 45.0,
                        ],
                        [
                            'color' => PdfFillColor::blue(),
                            'value' => 90.0,
                        ],
                    ],
                    'link' => 'https://google.com',
                ];
                $this->renderBarChart([$row], []);

                return true;
            }
        };
        $report->addPage();
        $report->resetStyle();
        $report->setLeftMargin(75.0);
        $report->setRightMargin(75.0);
        $report->setY($report->getPageHeight() / 2.0);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithRow(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfBarChartTrait;

            public function render(): bool
            {
                $row = [
                    'label' => 'Label',
                    'values' => [
                        [
                            'color' => PdfFillColor::black(),
                            'value' => 45.0,
                        ],
                        [
                            'color' => '#DC3545',
                            'value' => 90.0,
                        ],
                    ],
                    'link' => 'https://google.com',
                ];
                $this->renderBarChart([$row], []);

                return true;
            }
        };
        $report->addPage();
        $report->resetStyle();
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
