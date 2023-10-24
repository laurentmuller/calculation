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
 * @psalm-type PieChartRowType = array{color: PdfFillColor|string, value: int|float}
 */
trait PdfPieChartTrait
{
    use PdfSectorTrait;

    /**
     * Draw a pie chart.
     *
     * Each row to draw must contain a 'color' and a 'value' entry. Do nothing if the radius is not positive, the rows
     * are empty or if the sum of values is equal to 0.
     *
     * @param float                    $centerX   the abscissa of the center
     * @param float                    $centerY   the ordinate of the center
     * @param float                    $radius    the sector radius
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
        foreach ($rows as $row) {
            $this->_pieChartGetFillColor($row)->apply($this);
            $endAngle = $startAngle + 360.0 * (float) $row['value'] / $total;
            $this->sector($centerX, $centerY, $radius, $startAngle, $endAngle, $style, $clockwise, $origin);
            $startAngle = $endAngle;
        }
    }

    /**
     * @psalm-param PieChartRowType $row
     */
    private function _pieChartGetFillColor(array $row): PdfFillColor
    {
        $color = $row['color'];
        if (\is_string($color)) {
            $color = PdfFillColor::create($color);
        }
        if (!$color instanceof PdfFillColor) {
            return PdfFillColor::white();
        }

        return $color;
    }
}
