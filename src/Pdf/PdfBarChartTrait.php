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
use App\Pdf\Enums\PdfTextAlignment;
use App\Traits\MathTrait;

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
 *  @psalm-type BarChartRowType = array{
 *      label: string,
 *      values: BarChartValueType[]}
 * @psalm-type BarChartDataType = array{
 *      color: PdfFillColor|string,
 *      value: int|float,
 *      y: float,
 *      h: float}
 * @psalm-type BarChartRowDataType = array{
 *      label: string,
 *      label_width: float,
 *      x: float,
 *      w: float,
 *      values: BarChartDataType[]}
 * @psalm-type BarChartLabelType = array{
 *     label: string,
 *     label_width: float}
 */
trait PdfBarChartTrait
{
    use MathTrait;
    use PdfRotationTrait;

    private const SEP_BARS = 3.0;
    private const TEXT_ANGLE = 45.0;

    /**
     * Draw a bar chart.
     *
     * Does nothing if the rows are empty.
     *
     * @param array      $rows the data to draw
     * @param array      $axis the Y axis
     * @param float|null $x    the abscissa position or null to use the left margin
     * @param float|null $y    the ordinate position or null to use the current position
     * @param float|null $w    the width of null to use all the printable width
     * @param float|null $h    the height of null to use the default value (200)
     *
     * @psalm-param BarChartRowType[] $rows
     * @psalm-param BarChartAxisType $axis
     */
    public function barChart(
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

        $x ??= $this->getLeftMargin();
        $y ??= $this->GetY();
        $w ??= $this->getPrintableWidth();
        $h ??= 200.0;

        $endY = $y + $h;
        PdfStyle::getCellStyle()->apply($this);
        $cellMargin = $this->getCellMargin();
        $this->setCellMargin(0.0);

        // y axis values
        $min = (float) ($axis['min'] ?? 0.0);
        $max = (float) ($axis['max'] ?? $this->_barComputeMaxValue($rows));
        $max = $this->_barRoundMaxValue($max);
        $ticks = $this->_barGetTicks($max);
        $step = (float) ($axis['step'] ?? $max / (float) $ticks);
        $formatter = $axis['formatter'] ?? fn (int|float $value): string => (string) $value;

        $labelsY = $this->_barGetLabelsY($min, $max, $step, $formatter);
        $widthY = \max(\array_column($labelsY, 'label_width'));

        $labelsX = $this->_barGetLabelsX($rows);
        $widthX = \max(\array_column($labelsX, 'label_width'));

        $barWidth = $this->_barGetBarWidth($rows, $w - $widthY);
        $rotation = $barWidth < $widthX;

        // draw Y axis
        $h -= self::LINE_HEIGHT;
        if ($rotation) {
            $h -= \sin(self::TEXT_ANGLE) * $widthX - 1.0;
        } else {
            $h -= self::LINE_HEIGHT;
        }
        $this->_barDrawAxisY($labelsY, $widthY, $x, $y, $w, $h);

        // restrict data area
        $widthY += 1.0;
        $x += $widthY;
        $w -= $widthY;
        $y += self::LINE_HEIGHT / 2.0;

        // compute data
        $data = $this->_barComputeData($rows, $x, $y, $w, $h, $min, $max);

        // draw axis X and data
        $this->_barDrawAxisX($labelsX, $barWidth, $rotation, $x, $y + $h);
        $this->_barDrawData($data);

        // reset values
        $this->setCellMargin($cellMargin)
            ->resetStyle()
            ->SetY($endY);
    }

    /**
     * @psalm-param (BarChartValueType|BarChartDataType) $row
     */
    private function _barApplyFillColor(array $row): void
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
     * @psalm-param BarChartRowType[] $rows
     *
     * @psalm-return BarChartRowDataType[]
     */
    private function _barComputeData(
        array $rows,
        float $x,
        float $y,
        float $w,
        float $h,
        float $min,
        float $max
    ): array {
        $barWidth = $this->_barGetBarWidth($rows, $w);
        $step = self::SEP_BARS + $barWidth;
        $delta = $max - $min;
        $bottom = $y + $h;

        $result = [];
        $currentX = $x + self::SEP_BARS;
        foreach ($rows as $row) {
            $entry = [
                'label' => $row['label'],
                'label_width' => $this->GetStringWidth($row['label']),
                'x' => $currentX,
                'w' => $barWidth,
                'values' => [],
            ];
            $startY = $bottom;
            foreach ($row['values'] as $value) {
                $currentValue = $this->validateRange((float) $value['value'], $min, $max);
                $heightValue = $this->safeDivide($h * ($currentValue - $min), $delta);
                $entry['values'][] = [
                    'color' => $value['color'],
                    'value' => $currentValue,
                    'y' => $startY - $heightValue,
                    'h' => $heightValue,
                ];
                $startY -= $heightValue;
            }
            $currentX += $step;
            $result[] = $entry;
        }

        return $result;
    }

    /**
     * @psalm-param BarChartRowType[] $rows
     */
    private function _barComputeMaxValue(array $rows): float
    {
        $result = \PHP_FLOAT_MIN;
        foreach ($rows as $row) {
            $result = \max($result, \array_sum(\array_column($row['values'], 'value')));
        }

        return $result;
    }

    /**
     * @psalm-param BarChartRowType[] $rows
     */
    private function _barComputeMinValue(array $rows): float
    {
        $result = \PHP_FLOAT_MAX;
        foreach ($rows as $row) {
            $result = \min($result, \array_sum(\array_column($row['values'], 'value')));
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
        $x += self::SEP_BARS;
        foreach ($labels as $label) {
            $text = $label['label'];
            if ($rotation) {
                $width = $label['label_width'];
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
                $this->_barApplyFillColor($value);
                $this->Rect($row['x'], $value['y'], $row['w'], $value['h'], PdfRectangleStyle::BOTH);
            }
        }
    }

    private function _barGetBarWidth(array $rows, float $w): float
    {
        $countRows = (float) \count($rows);

        return ($w - self::SEP_BARS * $countRows) / $countRows;
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
                'label_width' => $this->GetStringWidth($row['label']),
            ];
        }, $rows);
    }

    /**
     * @psalm-param callable(int|float): string $formatter
     *
     * @psalm-return non-empty-array<BarChartLabelType>
     */
    private function _barGetLabelsY(
        float $min,
        float $max,
        float $step,
        callable $formatter
    ): array {
        /** @phpstan-var non-empty-array<BarChartLabelType> $result */
        $result = [];
        foreach (\range($max, $min, -$step) as $value) {
            $text = $formatter($value);
            $result[] = [
                'label' => $text,
                'label_width' => $this->GetStringWidth($text),
            ];
        }

        return $result;
    }

    private function _barGetTicks(float $value): int
    {
        $str = \rtrim((string) (int) $value, '0');

        return match (\substr($str, -2)) {
            '12' => 6,
            '14' => 7,
            '16' => 8,
            '25' => 10,
            default => match (\substr($str, -1)) {
                '1',
                '5' => 10,
                '2',
                '4',
                '8' => 8,
                '3',
                '6' => 6,
                '7' => 7,
                '9' => 9,
                default => 5,
            },
        };
    }

    private function _barRoundMaxValue(float $value): float
    {
        $len = \strlen((string) (int) $value);
        $max = \round($value, 1 - $len);
        if ($max >= $value) {
            return $max;
        }
        $step = $this->safeDivide($max, $this->_barGetTicks($max));
        if ($this->isFloatZero($step)) {
            return $max;
        }
        while ($max < $value) {
            $max += $step;
        }

        return $max;
    }
}
