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

namespace App\Pdf;

use App\Pdf\Enums\PdfRectangleStyle;
use App\Traits\MathTrait;

/**
 * Trait to draw pie chart.
 *
 * @psalm-type PieChartRowType = array{color: PdfFillColor|string, value: float, ...}
 * @psalm-type PieChartLegendType = array{color: PdfFillColor|string, label: string, ...}
 */
trait PdfPieChartTrait
{
    use MathTrait;
    use PdfEllipseTrait;
    use PdfSectorTrait;

    private const SEP_WIDTH = 1.5;

    /**
     * Draw a pie chart.
     *
     * Does nothing if the radius is not positive, if rows are empty, or if the sum of the values is equal to 0.
     *
     * @param float                    $centerX   the abscissa of the center
     * @param float                    $centerY   the ordinate of the center
     * @param float                    $radius    the radius
     * @param array                    $rows      the data to draw
     * @param PdfRectangleStyle|string $style     the draw and fill style
     * @param bool                     $clockwise indicates whether to go clockwise (true) or counter-clockwise (false)
     * @param float                    $origin    the origin of angles (0=right, 90=top, 180=left, 270=for bottom)
     *
     * @psalm-param PieChartRowType[] $rows
     */
    public function pieChart(
        float $centerX,
        float $centerY,
        float $radius,
        array $rows,
        PdfRectangleStyle|string $style = PdfRectangleStyle::BOTH,
        bool $clockwise = true,
        float $origin = 90
    ): void {
        if ($radius <= 0 || [] === $rows) {
            return;
        }
        $total = \array_sum(\array_column($rows, 'value'));
        if ($this->isFloatZero($total)) {
            return;
        }

        // check new page
        if (!$this->isPrintable($radius, $centerY + $radius)) {
            $this->AddPage();
            $centerY = $this->GetY() + $radius;
        }

        $startAngle = 0.0;
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($rows as $row) {
            $this->_pieApplyFillColor($row);
            $endAngle = $startAngle + 360.0 * $row['value'] / $total;
            $this->sector($centerX, $centerY, $radius, $startAngle, $endAngle, $style, $clockwise, $origin);
            $startAngle = $endAngle;
        }
        $this->resetStyle();
    }

    /**
     * Draw the horizontal pie chart legend.
     *
     * This function output a horizontal list where each entry contain the color and the label.
     *
     * Does nothing if legends are empty.
     *
     * @param array  $legends the legends to draw
     * @param ?float $x       the abscissa of the legend or null to center the list
     * @param ?float $y       the ordinate of the legend or null to use current position
     *
     * @psalm-param PieChartLegendType[] $legends
     */
    public function pieLegendHorizontal(array $legends, float $x = null, float $y = null): void
    {
        if ([] === $legends) {
            return;
        }

        $diameter = 2.0 * $this->_pieGetLegendRadius();
        $margins = 2.0 * $this->getCellMargin();
        $offset = $diameter + $margins + self::SEP_WIDTH;
        $widths = \array_map(fn (array $row): float => $this->GetStringWidth($row['label']) + $offset, $legends);
        if (null === $x) {
            $width = \array_sum($widths) - self::SEP_WIDTH;
            $x = $this->getLeftMargin() + ($this->getPrintableWidth() - $width) / 2.0;
        }
        $y ??= $this->GetY();
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $index => $legend) {
            $this->_pieOutputLegend($x, $y, $legend);
            $x += $widths[$index];
        }

        $this->resetStyle()
            ->Ln();
    }

    /**
     * Draw the vertical pie chart legend.
     *
     * This function output a vertical list where each line contain the color and the label. Does nothing if legends
     * are empty. After this call, the position is the same as before.
     *
     * @param array  $legends the legends to draw
     * @param ?float $x       the abscissa of the legend or null to use current position
     * @param ?float $y       the ordinate of the legend or null to use current position
     *
     * @psalm-param PieChartLegendType[] $legends
     */
    public function pieLegendVertical(array $legends, float $x = null, float $y = null): void
    {
        if ([] === $legends) {
            return;
        }

        [$oldX, $oldY] = $this->GetXY();
        $x ??= $oldX;
        $y ??= $oldY;

        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $legend) {
            $this->_pieOutputLegend($x, $y, $legend);
            $y += self::LINE_HEIGHT;
        }

        $this->resetStyle()
            ->SetXY($oldX, $oldY);
    }

    /**
     * @psalm-param (PieChartRowType|PieChartLegendType) $row
     */
    private function _pieApplyFillColor(array $row): void
    {
        $color = $row['color'];
        if (\is_string($color)) {
            $color = PdfFillColor::create($color);
        }
        if (!$color instanceof PdfFillColor) {
            $color = PdfFillColor::darkGray();
        }
        $color->apply($this);
    }

    private function _pieGetLegendRadius(): float
    {
        return (self::LINE_HEIGHT - 2.0 * $this->getCellMargin()) / 2.0;
    }

    /**
     * @psalm-param PieChartLegendType $legend
     */
    private function _pieOutputLegend(float $x, float $y, array $legend): void
    {
        $this->_pieApplyFillColor($legend);
        $radius = $this->_pieGetLegendRadius();
        $this->circle(
            $x + $radius,
            $y + $radius + $this->getCellMargin(),
            $radius,
            PdfRectangleStyle::BOTH
        );
        $this->SetXY($x + 2.0 * $radius, $y);
        $this->Cell(txt: $legend['label']);
    }
}
