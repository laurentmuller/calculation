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
use App\Pdf\Traits\PdfPieChartTrait;
use App\Report\AbstractReport;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-import-type ColorValueType from PdfChartInterface
 */
class PdfPieChartTraitTest extends TestCase
{
    public function testCounterClockwise(): void
    {
        $centerX = 250;
        $centerY = 250;
        $radius = 100;
        $row1 = [
            'color' => PdfFillColor::red(),
            'value' => 100.0,
        ];
        $row2 = [
            'color' => 'FF00FF',
            'value' => 100.0,
        ];
        $report = $this->createReport($centerX, $centerY, $radius, [$row1, $row2], false);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testEmptyRadius(): void
    {
        $centerX = 100;
        $centerY = 100;
        $radius = 0;
        $rows = [];
        $report = $this->createReport($centerX, $centerY, $radius, $rows);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testEmptyRows(): void
    {
        $centerX = 100;
        $centerY = 100;
        $radius = 100;
        $rows = [];
        $report = $this->createReport($centerX, $centerY, $radius, $rows);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testEmptySum(): void
    {
        $centerX = 100;
        $centerY = 100;
        $radius = 100;
        $row = [
            'color' => PdfFillColor::red(),
            'value' => 0.0,
        ];
        $report = $this->createReport($centerX, $centerY, $radius, [$row]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testEmptyValue(): void
    {
        $centerX = 250;
        $centerY = 250;
        $radius = 100;
        $row1 = [
            'color' => PdfFillColor::red(),
            'value' => 100.0,
        ];
        $row2 = [
            'color' => 'FF00FF',
            'value' => 0.0,
        ];
        $report = $this->createReport($centerX, $centerY, $radius, [$row1, $row2]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testFillColors(): void
    {
        $centerX = 250;
        $centerY = 250;
        $radius = 100;
        $row1 = [
            'color' => PdfFillColor::red(),
            'value' => 100.0,
        ];
        $row2 = [
            'color' => 'FF00FF',
            'value' => 100.0,
        ];
        $report = $this->createReport($centerX, $centerY, $radius, [$row1, $row2]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testInvalidFillColor(): void
    {
        $centerX = 100;
        $centerY = 100;
        $radius = 100;
        $row = [
            'color' => 'INVALID',
            'value' => 100.0,
        ];
        $report = $this->createReport($centerX, $centerY, $radius, [$row]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testNewPageAndColors(): void
    {
        $centerX = 250;
        $centerY = 100;
        $radius = 100;
        $row = [
            'color' => PdfFillColor::red(),
            'value' => 100.0,
        ];
        $report = $this->createReport($centerX, $centerY, $radius, [$row]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @psalm-param ColorValueType[] $rows
     */
    private function createReport(
        float $centerX,
        float $centerY,
        float $radius,
        array $rows,
        bool $clockwise = true,
    ): AbstractReport {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfPieChartTrait;

            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };
        $report->addPage()
            ->resetStyle();
        $report->renderPieChart($centerX, $centerY, $radius, $rows, clockwise: $clockwise);

        return $report;
    }
}
