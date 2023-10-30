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

/**
 * Trait to draw pie chart.
 *
 * @psalm-type PieChartRowType = array{color: PdfFillColor|string, value: int|float, ...}
 * @psalm-type PieChartLegendType = array{color: PdfFillColor|string, label: string, ...}
 */
trait PdfPieChartTrait
{
    use PdfSectorTrait;

    private const RECT_HEIGHT = 3.0;
    private const RECT_OFFSET = 1.0;
    private const RECT_WIDTH = 5.0;

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
        if (0.0 === $total) {
            return;
        }

        $startAngle = 0.0;
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($rows as $row) {
            $this->_pieApplyFillColor($row);
            $endAngle = $startAngle + 360.0 * (float) $row['value'] / $total;
            $this->sector($centerX, $centerY, $radius, $startAngle, $endAngle, $style, $clockwise, $origin);
            $startAngle = $endAngle;
        }
        $this->resetStyle();
    }

    /**
     * Draw the horizontal pie chart legend.
     *
     * This function output a horizontal list where each entry contain the color and the label. Does nothing if rows
     * are empty.
     *
     * @param array  $rows the legends to draw
     * @param ?float $x    the abscissa of the legend or null to center the list
     * @param ?float $y    the ordinate of the legend or null to use current position
     *
     * @psalm-param PieChartLegendType[] $rows
     */
    public function pieLegendHorizontal(array $rows, float $x = null, float $y = null): void
    {
        if ([] === $rows) {
            return;
        }

        $widths = \array_map(fn (array $row): float => $this->GetStringWidth($row['label']) + self::RECT_WIDTH, $rows);
        if (null === $x) {
            $width = \array_sum($widths) + (float) \count($rows) * self::RECT_WIDTH;
            $x = $this->getLeftMargin() + ($this->getPrintableWidth() - $width) / 2.0;
        }
        $y ??= $this->GetY();
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($rows as $index => $row) {
            $this->_pieOutputLegend($x, $y, $row);
            $x += $widths[$index] + self::RECT_WIDTH;
        }

        $this->resetStyle()
            ->Ln();
    }

    /**
     * Draw the vertical pie chart legend.
     *
     * This function output a vertical list where each line contain the color and the label. Does nothing if rows are
     * empty. After this call, the position is the same as before.
     *
     * @param array  $rows the legends to draw
     * @param ?float $x    the abscissa of the legend or null to use current position
     * @param ?float $y    the ordinate of the legend or null to use current position
     *
     * @psalm-param PieChartLegendType[] $rows
     */
    public function pieLegendVertical(array $rows, float $x = null, float $y = null): void
    {
        if ([] === $rows) {
            return;
        }

        [$oldX, $oldY] = $this->GetXY();
        $x ??= $oldX;
        $y ??= $oldY;

        PdfDrawColor::cellBorder()->apply($this);
        foreach ($rows as $row) {
            $this->_pieOutputLegend($x, $y, $row);
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
            $color = PdfFillColor::white();
        }
        $color->apply($this);
    }

    /**
     * @psalm-param PieChartLegendType $row
     */
    private function _pieOutputLegend(float $x, float $y, array $row): void
    {
        $this->SetXY($x, $y);
        $this->_pieApplyFillColor($row);
        $this->Rect($x, $y + self::RECT_OFFSET, self::RECT_WIDTH, self::RECT_HEIGHT, PdfRectangleStyle::BOTH);
        $this->SetXY($x + self::RECT_WIDTH, $y);
        $this->Cell(txt: $row['label']);
    }
}
