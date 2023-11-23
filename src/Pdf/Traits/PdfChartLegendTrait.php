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

/**
 * Trait to draw chart legends.
 *
 * @psalm-import-type ColorStringType from \App\Pdf\Interfaces\PdfChartInterface
 *
 * @psalm-require-extends \App\Pdf\PdfDocument
 *
 * @psalm-require-implements \App\Pdf\Interfaces\PdfChartInterface
 */
trait PdfChartLegendTrait
{
    use PdfEllipseTrait;

    private const SEP_WIDTH = 1.5;

    /**
     * Gets the height for the given legends.
     *
     * @param ColorStringType[] $legends    the legends to get height for
     * @param bool              $horizontal true for the horizontal legends; false for the vertical legends
     *
     * @return float the height used to output all legends or 0 if legends are empty
     */
    public function getLegendsHeight(array $legends, bool $horizontal): float
    {
        if ([] === $legends) {
            return 0.0;
        }

        $count = $horizontal ? 1 : \count($legends);

        return (float) $count * self::LINE_HEIGHT;
    }

    /**
     * Gets the width for the given legends.
     *
     * @param ColorStringType[] $legends    the legends to get width for
     * @param bool              $horizontal true for the horizontal legends; false for the vertical legends
     *
     * @return float the width used to output all legends or 0 if legends are empty
     */
    public function getLegendsWidth(array $legends, bool $horizontal): float
    {
        if ([] === $legends) {
            return 0.0;
        }

        $widths = $this->_getLegendWidths($legends);
        if ($horizontal) {
            return \array_sum($widths) + self::SEP_WIDTH * (float) (\count($legends) - 1);
        }

        return \max($widths);
    }

    /**
     * Draw the given legends horizontally or vertically.
     *
     * Does nothing if legends are empty. if horizontal value is false, the position is the same as before after this
     * call.
     *
     * @param ColorStringType[] $legends    the legends to draw
     * @param bool              $horizontal true to output legends as a horizontal list; false to output legends as
     *                                      a vertical list
     * @param ?float            $x          the abscissa of the legends or null to center the list (horizontal) or
     *                                      to use current position (vertical)
     * @param ?float            $y          the ordinate of the legends or null to use current position
     * @param bool              $circle     true to draw circle shapes; false to draw square shapes
     */
    public function legends(array $legends, bool $horizontal, float $x = null, float $y = null, bool $circle = true): static
    {
        if ([] === $legends) {
            return $this;
        }

        return $horizontal ?
            $this->legendsHorizontal($legends, $x, $y, $circle) :
            $this->legendsVertical($legends, $x, $y, $circle);
    }

    /**
     * Draw the given legends as a horizontal list.
     *
     * Does nothing if legends are empty.
     *
     * @param ColorStringType[] $legends the legends to draw
     * @param ?float            $x       the abscissa of the legends or null to center the list
     * @param ?float            $y       the ordinate of the legends or null to use current position
     * @param bool              $circle  true to a draw circle shape; false to draw a square shape
     */
    public function legendsHorizontal(array $legends, float $x = null, float $y = null, bool $circle = true): static
    {
        if ([] === $legends) {
            return $this;
        }

        $widths = $this->_getLegendWidths($legends);
        $totalWidth = $this->getLegendsWidth($legends, true);

        $y ??= $this->GetY();
        $x ??= $this->getLeftMargin() + ($this->getPrintableWidth() - $totalWidth) / 2.0;

        $radius = $this->_getLegendRadius();
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $index => $legend) {
            $this->_outputLegend($x, $y, $radius, $legend, $circle);
            $x += $widths[$index] + self::SEP_WIDTH;
        }
        $this->resetStyle()->Ln();

        return $this;
    }

    /**
     * Draw the given legends as a vertical list.
     *
     * Does nothing if legends are empty. After this call, the position is the same as before.
     *
     * @param ColorStringType[] $legends the legends to draw
     * @param ?float            $x       the abscissa of the legends or null to use current position
     * @param ?float            $y       the ordinate of the legends or null to use current position
     * @param bool              $circle  true to a draw circle shape; false to draw a square shape
     */
    public function legendsVertical(array $legends, float $x = null, float $y = null, bool $circle = true): static
    {
        if ([] === $legends) {
            return $this;
        }

        [$oldX, $oldY] = $this->GetXY();
        $x ??= $oldX;
        $y ??= $oldY;

        $radius = $this->_getLegendRadius();
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $legend) {
            $this->_outputLegend($x, $y, $radius, $legend, $circle);
            $y += self::LINE_HEIGHT;
        }
        $this->resetStyle()
            ->SetXY($oldX, $oldY);

        return $this;
    }

    /**
     * @psalm-param ColorStringType $legend
     */
    private function _applyLegendColor(array $legend): void
    {
        $color = $legend['color'];
        if (\is_string($color)) {
            $color = PdfFillColor::create($color);
        }
        $color ??= PdfFillColor::darkGray();
        $color->apply($this);
    }

    private function _getLegendRadius(): float
    {
        return (self::LINE_HEIGHT - 2.0 * $this->getCellMargin()) / 2.0;
    }

    /**
     * @psalm-param non-empty-array<ColorStringType> $legends
     *
     * @psalm-return non-empty-array<float>
     */
    private function _getLegendWidths(array $legends): array
    {
        $offset = 2.0 * ($this->_getLegendRadius() + $this->getCellMargin());

        return \array_map(fn (array $legend): float => $this->GetStringWidth($legend['label']) + $offset, $legends);
    }

    /**
     * @psalm-param ColorStringType $legend
     */
    private function _outputLegend(float $x, float $y, float $radius, array $legend, bool $circle = true): void
    {
        $diameter = 2.0 * $radius;
        $this->_applyLegendColor($legend);
        if ($circle) {
            $this->circle(
                $x + $radius,
                $y + $radius + $this->getCellMargin(),
                $radius,
                PdfRectangleStyle::BOTH
            );
        } else {
            $this->Rect(
                $x,
                $y + $this->getCellMargin(),
                $diameter,
                $diameter,
                PdfRectangleStyle::BOTH
            );
        }

        $this->SetXY($x + $diameter, $y);
        $this->Cell(txt: $legend['label']);
    }
}
