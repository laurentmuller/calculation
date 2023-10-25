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

/**
 * Trait to draw bar chart.
 *
 * @psalm-type BarChartAxisType = array{
 *     min?: int|float,
 *     max?: int|float,
 *     step?: int|float,
 *     formatter?: callable(int|float): string}
 * @psalm-type BarChartValueType = array{
 *     color: PdfFillColor|string,
 *     value: int|float}
 */
trait PdfBarChartTrait
{
    /**
     * Draw a bar chart.
     *
     * Does nothing if the rows are empty.
     *
     * @param array      $rows   the data to draw
     * @param array      $axis   the Y axis values
     * @param float|null $x      the abscissa position or null to use the left margin
     * @param float|null $y      the ordinate position or null to use the current position
     * @param float|null $width  the width of null to use all the printable width
     * @param float|null $height the height of null to use all the printable height
     *
     * @psalm-param array<BarChartValueType> $rows
     * @psalm-param BarChartAxisType $axis
     */
    public function barChart(
        array $rows,
        array $axis,
        float $x = null,
        float $y = null,
        float $width = null,
        float $height = null
    ): void {
    }
}
