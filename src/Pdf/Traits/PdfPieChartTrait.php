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

namespace App\Pdf\Traits;

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Enums\PdfRectangleStyle;
use App\Traits\ArrayTrait;

/**
 * Trait to draw pie chart.
 *
 * @psalm-import-type ColorValueType from \App\Pdf\Interfaces\PdfChartInterface
 *
 * @psalm-require-extends \App\Pdf\PdfDocument
 *
 * @psalm-require-implements \App\Pdf\Interfaces\PdfChartInterface
 */
trait PdfPieChartTrait
{
    use ArrayTrait;
    use PdfSectorTrait;

    /**
     * Draw a pie chart.
     *
     * Does nothing if the radius is not positive, if rows are empty, or if the sum of the values is equal to 0.
     *
     * @param float                    $centerX   the abscissa of the center
     * @param float                    $centerY   the ordinate of the center
     * @param float                    $radius    the radius
     * @param ColorValueType[]         $rows      the data to draw
     * @param PdfRectangleStyle|string $style     the draw and fill style
     * @param bool                     $clockwise indicates whether to go clockwise (true) or counter-clockwise (false)
     * @param float                    $origin    the origin of angles (0=right, 90=top, 180=left, 270=for bottom)
     */
    public function renderPieChart(
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
        $total = $this->getColumnSum($rows, 'value');
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
     * @psalm-param ColorValueType $row
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
}
