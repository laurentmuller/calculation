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
use App\Pdf\PdfStyle;
use App\Pdf\PdfYaxis;
use App\Traits\ArrayTrait;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\Traits\PdfRotationTrait;

/**
 * Trait to draw bar chart.
 *
 * @phpstan-import-type ColorValueType from \App\Pdf\Interfaces\PdfChartInterface
 * @phpstan-import-type BarChartRowType from \App\Pdf\Interfaces\PdfChartInterface
 * @phpstan-import-type BarChartAxisType from \App\Pdf\Interfaces\PdfChartInterface
 *
 * @phpstan-type BarChartDataType = array{
 *      color: PdfFillColor,
 *      value: float,
 *      y: float,
 *      h: float}
 * @phpstan-type BarChartRowDataType = array{
 *      label: string,
 *      link: string|int|null,
 *      width: float,
 *      x: float,
 *      w: float,
 *      values: BarChartDataType[]}
 * @phpstan-type BarChartLabelType = array{
 *     label: string,
 *     width: float}
 *
 * @phpstan-require-extends \fpdf\PdfDocument
 *
 * @phpstan-require-implements \App\Pdf\Interfaces\PdfChartInterface
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
     * @param array      $rows   the data to draw
     * @param array      $axis   the Y axis definition
     * @param float|null $x      the abscissa position or null to use the left margin
     * @param float|null $y      the ordinate position or null to use the current position
     * @param float|null $width  the width or null to use all the printable width
     * @param float|null $height the height or null to use the default value (200)
     *
     * @phpstan-param BarChartRowType[] $rows
     * @phpstan-param BarChartAxisType $axis
     */
    public function renderBarChart(
        array $rows,
        array $axis,
        ?float $x = null,
        ?float $y = null,
        ?float $width = null,
        ?float $height = null
    ): void {
        if ([] === $rows) {
            return;
        }

        // get values
        $x ??= $this->getLeftMargin();
        $y ??= $this->getY();
        $width ??= $this->getPrintableWidth();
        $height ??= 200.0;
        $endY = $y + $height;

        // check the new page
        if (!$this->isPrintable($height, $y)) {
            $this->addPage();
            $y = $this->getY();
            $endY = $y + $height;
        }

        // init
        PdfStyle::getCellStyle()->apply($this);
        $margin = $this->getCellMargin();
        $this->setCellMargin(0.0);

        // y axis values
        $min = $axis['min'] ?? $this->computeBarRowsValues($rows, static fn (float $a, float $b): float => \min($a, $b));
        $max = $axis['max'] ?? $this->computeBarRowsValues($rows, static fn (float $a, float $b): float => \max($a, $b));

        // y axis
        $scale = PdfYaxis::instance($min, $max);
        $labelsY = $scale->getLabels($this, $axis['formatter'] ?? null);
        $widthY = $this->getColumnMax($labelsY, 'width');

        // x axis
        $labelsX = $this->getBarLabelsX($rows);
        $widthX = $this->getColumnMax($labelsX, 'width');
        $barWidth = $this->getBarWidth($rows, $width - $widthY - 1.0);
        $rotation = $barWidth < $widthX;

        // draw Y axis
        $height -= self::LINE_HEIGHT;
        if ($rotation) {
            $height -= \sin(self::TEXT_ANGLE) * $widthX - 1.0;
        } else {
            $height -= self::LINE_HEIGHT;
        }
        $this->drawBarAxisY($labelsY, $widthY, $x, $y, $width, $height);

        // restrict axis x area
        $x += $widthY + 1.0 + self::SEP_BARS;
        $y += self::LINE_HEIGHT / 2.0;

        // draw axis X and data
        $data = $this->computeBarData($rows, $barWidth, $x, $y, $height, $scale);
        $this->drawBarAxisX($labelsX, $barWidth, $rotation, $x, $y + $height);
        $this->drawBarData($data);

        // reset
        $this->setCellMargin($margin);
        $this->resetStyle()
            ->setY($endY);
    }

    /**
     * @phpstan-param BarChartRowType[] $rows
     *
     * @phpstan-return BarChartRowDataType[]
     */
    private function computeBarData(
        array $rows,
        float $barWidth,
        float $x,
        float $y,
        float $h,
        PdfYaxis $scale
    ): array {
        $result = [];
        $bottom = $y + $h;
        $min = $scale->lowerBound;
        $max = $scale->upperBound;
        $delta = $max - $min;
        $step = self::SEP_BARS + $barWidth;

        $currentX = $x;
        foreach ($rows as $row) {
            $entry = [
                'label' => $row['label'],
                'link' => $row['link'] ?? null,
                'width' => $this->getStringWidth($row['label']),
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
                    'color' => $this->getBarFillColor($value),
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
     * @phpstan-param non-empty-array<BarChartRowType> $rows
     * @phpstan-param callable(float, float): float $callback
     */
    private function computeBarRowsValues(array $rows, callable $callback): float
    {
        $result = null;
        foreach ($rows as $row) {
            $values = $this->getColumnSum($row['values'], 'value');
            $result = null === $result ? $values : $callback($result, $values);
        }

        return $result;
    }

    /**
     * @phpstan-param BarChartLabelType[] $labels
     */
    private function drawBarAxisX(
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
                $this->rotateText($text, self::TEXT_ANGLE, $x + $dx, $y + $dy);
            } else {
                $this->setXY($x, $y);
                $this->cell(width: $barWidth, text: $text, align: PdfTextAlignment::CENTER);
            }
            $x += $barWidth + self::SEP_BARS;
        }
    }

    /**
     * @phpstan-param  BarChartLabelType[] $labels
     */
    private function drawBarAxisY(
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
            $this->setXY($x, $y);
            $this->cell(width: $width, text: $text, align: PdfTextAlignment::RIGHT);
            $this->line($x + $width + 1.0, $y + $halfHeight, $x + $w, $y + $halfHeight);
            $y += $deltaY;
        }
    }

    /**
     * @phpstan-param BarChartRowDataType[] $data
     */
    private function drawBarData(array $data): void
    {
        foreach ($data as $row) {
            foreach ($row['values'] as $value) {
                $value['color']->apply($this);
                $this->rect(
                    $row['x'],
                    $value['y'],
                    $row['w'],
                    $value['h'],
                    PdfRectangleStyle::FILL,
                    $row['link']
                );
            }
        }
    }

    /**
     * @phpstan-param ColorValueType $row
     */
    private function getBarFillColor(array $row): PdfFillColor
    {
        $color = $row['color'];
        if (\is_string($color)) {
            $color = PdfFillColor::create($color);
        }

        return $color ?? PdfFillColor::darkGray();
    }

    /**
     * @phpstan-param non-empty-array<BarChartRowType> $rows
     *
     * @phpstan-return non-empty-array<BarChartLabelType>
     */
    private function getBarLabelsX(array $rows): array
    {
        return \array_map(fn (array $row): array => [
            'label' => $row['label'],
            'width' => $this->getStringWidth($row['label']),
        ], $rows);
    }

    private function getBarWidth(array $rows, float $width): float
    {
        $count = (float) \count($rows);

        return ($width - ($count + 1.0) * self::SEP_BARS) / $count;
    }
}
