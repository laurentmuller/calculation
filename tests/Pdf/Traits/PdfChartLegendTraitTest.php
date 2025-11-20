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
use App\Pdf\Enums\PdfPointStyle;
use App\Pdf\Interfaces\PdfChartInterface;
use App\Tests\Fixture\FixturePdfChartLegendReport;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type ColorStringType from PdfChartInterface
 */
final class PdfChartLegendTraitTest extends TestCase
{
    public function testLegends(): void
    {
        $report = $this->createReport();
        $report->legends(legends: [], horizontal: true);
        $report->legends(legends: [], horizontal: false);
        $legends = $this->createLegends();
        $report->legends(legends: $legends, horizontal: true);
        $report->legends(legends: $legends, horizontal: true, style: PdfPointStyle::SQUARE);
        $report->legends(legends: $legends, horizontal: false);
        $report->legends(legends: $legends, horizontal: false, style: PdfPointStyle::SQUARE);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testLegendsHeight(): void
    {
        $report = $this->createReport();
        $actual = $report->getLegendsHeight(legends: [], horizontal: true);
        self::assertSame(0.0, $actual);
        $actual = $report->getLegendsHeight(legends: [], horizontal: false);
        self::assertSame(0.0, $actual);

        $legends = $this->createLegends();
        $actual = $report->getLegendsHeight(legends: $legends, horizontal: true);
        self::assertSame(5.0, $actual);
        $actual = $report->getLegendsHeight(legends: $legends, horizontal: false);
        self::assertSame(10.0, $actual);
    }

    public function testLegendsHorizontal(): void
    {
        $report = $this->createReport();
        $report->legendsHorizontal(legends: []);
        $report->legendsHorizontal(legends: [], style: PdfPointStyle::SQUARE);
        $legends = $this->createLegends();
        $report->legendsHorizontal(legends: $legends);
        $report->legendsHorizontal(legends: $legends, style: PdfPointStyle::SQUARE);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testLegendsVertical(): void
    {
        $report = $this->createReport();
        $report->legendsVertical(legends: []);
        $report->legendsVertical(legends: [], style: PdfPointStyle::SQUARE);
        $legends = $this->createLegends();
        $report->legendsVertical(legends: $legends);
        $report->legendsVertical(legends: $legends, style: PdfPointStyle::SQUARE);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testLegendsWidths(): void
    {
        $report = $this->createReport();
        $actual = $report->getLegendsWidth(legends: [], horizontal: true);
        self::assertSame(0.0, $actual);
        $actual = $report->getLegendsWidth(legends: [], horizontal: false);
        self::assertSame(0.0, $actual);

        $legends = $this->createLegends();
        $actual = $report->getLegendsWidth(legends: $legends, horizontal: true);
        self::assertEqualsWithDelta(27.03, $actual, 0.1);
        $actual = $report->getLegendsWidth(legends: $legends, horizontal: false);
        self::assertEqualsWithDelta(12.76, $actual, 0.1);
    }

    public function testShapes(): void
    {
        $report = $this->createReport();
        $legends = $this->createLegends();

        $styles = PdfPointStyle::cases();
        foreach ($styles as $style) {
            $report->legends(legends: $legends, horizontal: true, style: $style);
            $actual = $report->render();
            self::assertTrue($actual);
        }
    }

    /**
     * @phpstan-return ColorStringType[]
     */
    private function createLegends(): array
    {
        $legend1 = [
            'color' => PdfFillColor::blue(),
            'label' => 'Label',
        ];
        $legend2 = [
            'color' => 'DC3545',
            'label' => 'Label',
        ];

        return [$legend1, $legend2];
    }

    private function createReport(): FixturePdfChartLegendReport
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new FixturePdfChartLegendReport($controller);
        $report->resetStyle()
            ->addPage();

        return $report;
    }
}
