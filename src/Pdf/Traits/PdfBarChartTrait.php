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

use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Enums\PdfRectangleStyle;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBarScale;
use App\Pdf\PdfStyle;
use App\Traits\ArrayTrait;

/**
 * Trait to draw bar chart.
 *
 * @psalm-import-type ColorValueType from \App\Pdf\Interfaces\PdfChartInterface
 * @psalm-import-type BarChartRowType from \App\Pdf\Interfaces\PdfChartInterface
 * @psalm-import-type BarChartAxisType from \App\Pdf\Interfaces\PdfChartInterface
 *
 * @psalm-type BarChartDataType = array{
 *      color: PdfFillColor,
 *      value: float,
 *      y: float,
 *      h: float}
 * @psalm-type BarChartRowDataType = array{
 *      label: string,
 *      width: float,
 *      x: float,
 *      w: float,
 *      values: BarChartDataType[]}
 * @psalm-type BarChartLabelType = array{
 *     label: string,
 *     width: float}
 *
 * @psalm-require-extends \App\Pdf\PdfDocument
 *
 * @psalm-require-implements \App\Pdf\Interfaces\PdfChartInterface
 */
trait PdfBarChartTrait
{
    use ArrayTrait;
    use PdfRotationTrait;

    private const SEP_BARS = 3.0;
    private const TEXT_ANGLE = 45.0;

    /**
     * Draw a bar chart.
     *
     * Does nothing if rows are empty.
     *
     * @param array      $rows the data to draw
     * @param array      $axis the Y axis definition
     * @param float|null $x    the abscissa position or null to use the left margin
     * @param float|null $y    the ordinate position or null to use the current position
     * @param float|null $w    the width or null to use all the printable width
     * @param float|null $h    the height or null to use the default value (200)
     *
     * @psalm-param BarChartRowType[] $rows
     * @psalm-param BarChartAxisType $axis
     */
    public function renderBarChart(
        array $rows,
        array $axis,
        float $x = null,
        float $y = null,
        float $w = null,
        float $h = null
    ): void {
        if ([] === $rows) {
            return;
        }

        // get values
        $x ??= $this->getLeftMargin();
        $y ??= $this->GetY();
        $w ??= $this->getPrintableWidth();
        $h ??= 200.0;
        $endY = $y + $h;

        // check new page
        if (!$this->isPrintable($h, $y)) {
            $this->AddPage();
            $y = $this->GetY();
            $endY = $y + $h;
        }

        // init
        PdfStyle::getCellStyle()->apply($this);
        $margin = $this->getCellMargin();
        $this->setCellMargin(0.0);

        // y axis values
        $min = $axis['min'] ?? $this->_barComputeRowsValues($rows, fn (float $a, float $b): float => \min($a, $b));
        $max = $axis['max'] ?? $this->_barComputeRowsValues($rows, fn (float $a, float $b): float => \max($a, $b));
        $formatter = $axis['formatter'] ?? fn (float $value): string => (string) $value;

        // y axis
        $scale = new PdfBarScale($min, $max);
        $labelsY = $this->_barGetLabelsY($scale, $formatter);
        $widthY = $this->getColumnMax($labelsY, 'width');

        // x axis
        $labelsX = $this->_barGetLabelsX($rows);
        $widthX = $this->getColumnMax($labelsX, 'width');
        $barWidth = $this->_barGetBarWidth($rows, $w - $widthY - 1.0);
        $rotation = $barWidth < $widthX;

        // draw Y axis
        $h -= self::LINE_HEIGHT;
        if ($rotation) {
            $h -= \sin(self::TEXT_ANGLE) * $widthX - 1.0;
        } else {
            $h -= self::LINE_HEIGHT;
        }
        $this->_barDrawAxisY($labelsY, $widthY, $x, $y, $w, $h);

        // restrict axis x area
        $x += $widthY + 1.0 + self::SEP_BARS;
        $y += self::LINE_HEIGHT / 2.0;

        // draw axis X and data
        $data = $this->_barComputeData($rows, $barWidth, $x, $y, $h, $scale);
        $this->_barDrawAxisX($labelsX, $barWidth, $rotation, $x, $y + $h);
        $this->_barDrawData($data);

        // reset
        $this->setCellMargin($margin)
            ->resetStyle()
            ->SetY($endY);
    }

    /**
     * @psalm-param BarChartRowType[] $rows
     *
     * @psalm-return BarChartRowDataType[]
     */
    private function _barComputeData(
        array $rows,
        float $barWidth,
        float $x,
        float $y,
        float $h,
        PdfBarScale $scale
    ): array {
        $result = [];
        $bottom = $y + $h;
        $min = $scale->getLowerBound();
        $max = $scale->getUpperBound();
        $delta = $max - $min;
        $step = self::SEP_BARS + $barWidth;

        $currentX = $x;
        foreach ($rows as $row) {
            $entry = [
                'label' => $row['label'],
                'width' => $this->GetStringWidth($row['label']),
                'x' => $currentX,
                'w' => $barWidth,
                'values' => [],
            ];
            $startY = $bottom;
            foreach ($row['values'] as $value) {
                $currentValue = $this->validateRange($value['value'], $min, $max);
                $heightValue = $this->safeDivide($h * ($currentValue - $min), $delta);
                if (($startY - $heightValue) < $y) {
                    continue;
                }
                $entry['values'][] = [
                    'color' => $this->_barGetFillColor($value),
                    'value' => $currentValue,
                    'y' => $startY - $heightValue,
                    'h' => $heightValue,
                ];
                $startY -= $heightValue;
            }
            $result[] = $entry;
            $currentX += $step;
        }

        return $result;
    }

    /**
     * @psalm-param non-empty-array<BarChartRowType> $rows
     * @psalm-param callable(float, float): float $callback
     */
    private function _barComputeRowsValues(array $rows, callable $callback): float
    {
        $result = null;
        foreach ($rows as $row) {
            $values = $this->getColumnSum($row['values'], 'value');
            $result = null === $result ? $values : $callback($result, $values);
        }

        return $result;
    }

    /**
     * @psalm-param BarChartLabelType[] $labels
     */
    private function _barDrawAxisX(
        array $labels,
        float $barWidth,
        bool $rotation,
        float $x,
        float $y
    ): void {
        foreach ($labels as $label) {
            $text = $label['label'];
            if ($rotation) {
                $width = $label['width'];
                $dx = $barWidth / 2.0 - \cos(self::TEXT_ANGLE) * ($width + self::LINE_HEIGHT);
                $dy = \sin(self::TEXT_ANGLE) * ($width + 1.0);
                $this->RotateText($text, self::TEXT_ANGLE, $x + $dx, $y + $dy);
            } else {
                $this->SetXY($x, $y);
                $this->Cell(w: $barWidth, txt: $text, align: PdfTextAlignment::CENTER);
            }
            $x += $barWidth + self::SEP_BARS;
        }
    }

    /**
     * @psalm-param  BarChartLabelType[] $labels
     */
    private function _barDrawAxisY(
        array $labels,
        float $width,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $halfHeight = self::LINE_HEIGHT / 2.0;
        $deltaY = $this->safeDivide($h, \count($labels) - 1);
        foreach ($labels as $label) {
            $text = $label['label'];
            $this->SetXY($x, $y);
            $this->Cell(w: $width, txt: $text, align: PdfTextAlignment::RIGHT);
            $this->Line($x + $width + 1.0, $y + $halfHeight, $x + $w, $y + $halfHeight);
            $y += $deltaY;
        }
    }

    /**
     * @psalm-param BarChartRowDataType[] $data
     */
    private function _barDrawData(array $data): void
    {
        foreach ($data as $row) {
            foreach ($row['values'] as $value) {
                $value['color']->apply($this);
                $this->Rect($row['x'], $value['y'], $row['w'], $value['h'], PdfRectangleStyle::FILL);
            }
        }
    }

    private function _barGetBarWidth(array $rows, float $w): float
    {
        $countRows = (float) \count($rows);

        return ($w - ($countRows + 1.0) * self::SEP_BARS) / $countRows;
    }

    /**
     * @psalm-param ColorValueType $row
     */
    private function _barGetFillColor(array $row): PdfFillColor
    {
        $color = $row['color'];
        if (\is_string($color)) {
            $color = PdfFillColor::create($color);
        }

        return $color instanceof PdfFillColor ? $color : PdfFillColor::darkGray();
    }

    /**
     * @psalm-param non-empty-array<BarChartRowType> $rows
     *
     * @psalm-return non-empty-array<BarChartLabelType>
     */
    private function _barGetLabelsX(array $rows): array
    {
        return \array_map(function (array $row): array {
            return [
                'label' => $row['label'],
                'width' => $this->GetStringWidth($row['label']),
            ];
        }, $rows);
    }

    /**
     * @psalm-param callable(float): string $formatter
     *
     * @psalm-return non-empty-array<BarChartLabelType>
     */
    private function _barGetLabelsY(PdfBarScale $scale, callable $formatter): array
    {
        /** @psalm-var non-empty-array<BarChartLabelType> $result */
        $result = [];
        foreach (\range($scale->getUpperBound(), $scale->getLowerBound(), -$scale->getTickSpacing()) as $value) {
            $text = $formatter($value);
            $result[] = [
                'label' => $text,
                'width' => $this->GetStringWidth($text),
            ];
        }

        return $result;
    }
}
