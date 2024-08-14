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
use App\Pdf\Interfaces\PdfChartInterface;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Traits\PdfEllipseTrait;

/**
 * Trait to draw chart legends.
 *
 * @psalm-import-type ColorStringType from PdfChartInterface
 *
 * @psalm-require-extends \App\Report\AbstractReport
 *
 * @psalm-require-implements PdfChartInterface
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

        $widths = $this->getLegendWidths($legends);
        if ($horizontal) {
            return \array_sum($widths) + self::SEP_WIDTH * (float) (\count($legends) - 1);
        }

        return \max($widths);
    }

    /**
     * Draw the given legends horizontally or vertically.
     *
     * Does nothing if legends are empty. If horizontal value is false, the position is the same as before after this
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
    public function legends(array $legends, bool $horizontal, ?float $x = null, ?float $y = null, bool $circle = true): static
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
    public function legendsHorizontal(array $legends, ?float $x = null, ?float $y = null, bool $circle = true): static
    {
        if ([] === $legends) {
            return $this;
        }

        $widths = $this->getLegendWidths($legends);
        $totalWidth = $this->getLegendsWidth($legends, true);

        $y ??= $this->getY();
        $x ??= $this->getLeftMargin() + ($this->getPrintableWidth() - $totalWidth) / 2.0;

        $radius = $this->getLegendRadius();
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $index => $legend) {
            $this->outputLegend($x, $y, $radius, $legend, $circle);
            $x += $widths[$index] + self::SEP_WIDTH;
        }
        $this->resetStyle()->lineBreak();

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
     * @param bool              $circle  true to draw a circle shape; false to draw a square shape
     */
    public function legendsVertical(array $legends, ?float $x = null, ?float $y = null, bool $circle = true): static
    {
        if ([] === $legends) {
            return $this;
        }

        $position = $this->getPosition();
        $x ??= $position->x;
        $y ??= $position->y;

        $radius = $this->getLegendRadius();
        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $legend) {
            $this->outputLegend($x, $y, $radius, $legend, $circle);
            $y += self::LINE_HEIGHT;
        }
        $this->resetStyle()
            ->setPosition($position);

        return $this;
    }

    /**
     * @psalm-param ColorStringType $legend
     */
    private function applyLegendColor(array $legend): void
    {
        $color = $legend['color'];
        if (\is_string($color)) {
            $color = PdfFillColor::create($color);
        }
        $color ??= PdfFillColor::darkGray();
        $color->apply($this);
    }

    private function getLegendRadius(): float
    {
        return (self::LINE_HEIGHT - 2.0 * $this->getCellMargin()) / 2.0;
    }

    /**
     * @psalm-param non-empty-array<ColorStringType> $legends
     *
     * @psalm-return non-empty-array<float>
     */
    private function getLegendWidths(array $legends): array
    {
        $offset = 2.0 * ($this->getLegendRadius() + $this->getCellMargin());

        return \array_map(fn (array $legend): float => $this->getStringWidth($legend['label']) + $offset, $legends);
    }

    /**
     * @psalm-param ColorStringType $legend
     */
    private function outputLegend(float $x, float $y, float $radius, array $legend, bool $circle = true): void
    {
        $diameter = 2.0 * $radius;
        $this->applyLegendColor($legend);
        if ($circle) {
            $this->circle(
                $x + $radius,
                $y + $radius + $this->getCellMargin(),
                $radius,
                PdfRectangleStyle::BOTH
            );
        } else {
            $this->rect(
                $x,
                $y + $this->getCellMargin(),
                $diameter,
                $diameter,
                PdfRectangleStyle::BOTH
            );
        }

        $this->setXY($x + $diameter, $y);
        $this->cell(text: $legend['label']);
    }
}
