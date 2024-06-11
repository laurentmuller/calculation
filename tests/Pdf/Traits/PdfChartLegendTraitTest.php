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
use App\Pdf\Traits\PdfChartLegendTrait;
use App\Report\AbstractReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-import-type ColorStringType from PdfChartInterface
 */
#[CoversClass(PdfChartLegendTrait::class)]
class PdfChartLegendTraitTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testLegends(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfChartLegendTrait;

            public function render(): bool
            {
                return true;
            }

            /**
             * @psalm-param ColorStringType[] $legends
             *
             * @phpstan-param array<array{color: PdfFillColor|string, label: string, ...}> $legends
             */
            public function outputLegends(array $legends, bool $horizontal, bool $circle = true): static
            {
                return $this->legends($legends, $horizontal, circle: $circle);
            }
        };
        $report->resetStyle()
            ->addPage();

        $report->outputLegends([], true);
        $report->outputLegends([], false);
        $legends = $this->getLegends();
        $report->outputLegends($legends, true);
        $report->outputLegends($legends, true, false);
        $report->outputLegends($legends, false);
        $report->outputLegends($legends, false, false);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testLegendsHeight(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfChartLegendTrait;

            public function render(): bool
            {
                return true;
            }

            /**
             * @psalm-param ColorStringType[] $legends
             *
             * @phpstan-param array<array{color: PdfFillColor|string, label: string, ...}> $legends
             */
            public function computeHeights(array $legends, bool $horizontal): float
            {
                return $this->getLegendsHeight($legends, $horizontal);
            }
        };
        $report->resetStyle()
            ->addPage();
        $actual = $report->computeHeights([], true);
        self::assertSame(0.0, $actual);
        $actual = $report->computeHeights([], false);
        self::assertSame(0.0, $actual);

        $legends = $this->getLegends();
        $actual = $report->computeHeights($legends, true);
        self::assertSame(5.0, $actual);
        $actual = $report->computeHeights($legends, false);
        self::assertSame(10.0, $actual);
    }

    /**
     * @throws Exception
     */
    public function testLegendsHorizontal(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfChartLegendTrait;

            public function render(): bool
            {
                return true;
            }

            /**
             * @psalm-param ColorStringType[] $legends
             *
             * @phpstan-param array<array{color: PdfFillColor|string, label: string, ...}> $legends
             */
            public function outputLegendsHorizontal(array $legends, bool $circle = true): static
            {
                return $this->legendsHorizontal($legends, circle: $circle);
            }
        };
        $report->resetStyle()
            ->addPage();

        $report->outputLegendsHorizontal([], true);
        $report->outputLegendsHorizontal([], false);
        $legends = $this->getLegends();
        $report->outputLegendsHorizontal($legends, true);
        $report->outputLegendsHorizontal($legends, false);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testLegendsVertical(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfChartLegendTrait;

            public function render(): bool
            {
                return true;
            }

            /**
             * @psalm-param ColorStringType[] $legends
             *
             * @phpstan-param array<array{color: PdfFillColor|string, label: string, ...}> $legends
             */
            public function outputLegendsVertical(array $legends, bool $circle = true): static
            {
                return $this->legendsVertical($legends, circle: $circle);
            }
        };
        $report->resetStyle()
            ->addPage();

        $report->outputLegendsVertical([], true);
        $report->outputLegendsVertical([], false);
        $legends = $this->getLegends();
        $report->outputLegendsVertical($legends, true);
        $report->outputLegendsVertical($legends, false);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testLegendsWidths(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport implements PdfChartInterface {
            use PdfChartLegendTrait;

            public function render(): bool
            {
                return true;
            }

            /**
             * @psalm-param ColorStringType[] $legends
             *
             * @phpstan-param array<array{color: PdfFillColor|string, label: string, ...}> $legends
             */
            public function computeWidths(array $legends, bool $horizontal): float
            {
                return $this->getLegendsWidth($legends, $horizontal);
            }
        };
        $report->resetStyle()
            ->addPage();

        $actual = $report->computeWidths([], true);
        self::assertSame(0.0, $actual);
        $actual = $report->computeWidths([], false);
        self::assertSame(0.0, $actual);

        $legends = $this->getLegends();
        $actual = $report->computeWidths($legends, true);
        self::assertEqualsWithDelta(27.03, $actual, 0.1);
        $actual = $report->computeWidths($legends, false);
        self::assertEqualsWithDelta(12.76, $actual, 0.1);
    }

    /**
     * @psalm-return ColorStringType[]
     *
     * @phpstan-return array<array{color: PdfFillColor|string, label: string, ...}>
     */
    private function getLegends(): array
    {
        $legend1 = [
            'color' => PdfFillColor::link(),
            'label' => 'Label',
        ];
        $legend2 = [
            'color' => 'DC3545',
            'label' => 'Label',
        ];

        return [$legend1, $legend2];
    }
}
