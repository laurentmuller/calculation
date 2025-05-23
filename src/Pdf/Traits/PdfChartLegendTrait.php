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
use App\Pdf\Enums\PdfPointStyle;
use App\Pdf\Interfaces\PdfChartInterface;
use fpdf\Color\PdfRgbColor;

/**
 * Trait to draw chart legends.
 *
 * @phpstan-import-type ColorStringType from PdfChartInterface
 *
 * @phpstan-require-extends \fpdf\PdfDocument
 *
 * @phpstan-require-implements PdfChartInterface
 */
trait PdfChartLegendTrait
{
    use PdfPointStyleTrait;

    /**
     * The width of separation between legends.
     */
    private const SEP_WIDTH = 1.5;

    /**
     * Gets the height for the given legends.
     *
     * @param array $legends    the legends to get height for
     * @param bool  $horizontal true for the horizontal legends; false for the vertical legends
     *
     * @return float the height used to output all legends or 0 if legends are empty
     *
     * @phpstan-param ColorStringType[] $legends
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
     * @param array         $legends    the legends to get width for
     * @param bool          $horizontal true for the horizontal legends; false for the vertical legends
     * @param PdfPointStyle $style      the type of shape to draw
     *
     * @return float the width used to output all legends or 0 if legends are empty
     *
     * @phpstan-param ColorStringType[] $legends
     */
    public function getLegendsWidth(
        array $legends,
        bool $horizontal,
        PdfPointStyle $style = PdfPointStyle::CIRCLE
    ): float {
        if ([] === $legends) {
            return 0.0;
        }

        $widths = $this->getLegendWidths($legends, $style);
        if ($horizontal) {
            return \array_sum($widths) + self::SEP_WIDTH * (float) (\count($legends) - 1);
        }

        return \max($widths);
    }

    /**
     * Draw the given legends horizontally or vertically.
     *
     * Does nothing if legends are empty.
     * If the horizontal value is false, the position is the same as before after this call.
     *
     * @param array         $legends    the legends to draw
     * @param bool          $horizontal true to output legends as a horizontal list; false to output legends as
     *                                  a vertical list
     * @param ?float        $x          the abscissa of the legends or null to center the list (horizontal) or
     *                                  to use current position (vertical)
     * @param ?float        $y          the ordinate of the legends or null to use the current position
     * @param PdfPointStyle $style      the type of shape to draw
     *
     * @phpstan-param ColorStringType[] $legends
     */
    public function legends(
        array $legends,
        bool $horizontal,
        ?float $x = null,
        ?float $y = null,
        PdfPointStyle $style = PdfPointStyle::CIRCLE
    ): static {
        if ([] === $legends) {
            return $this;
        }

        return $horizontal ?
            $this->legendsHorizontal($legends, $x, $y, $style) :
            $this->legendsVertical($legends, $x, $y, $style);
    }

    /**
     * Draw the given legends as a horizontal list.
     *
     * Does nothing if legends are empty.
     *
     * @param array         $legends the legends to draw
     * @param ?float        $x       the abscissa of the legends or null to center the abscissa
     * @param ?float        $y       the ordinate of the legends or null to use the current ordinate
     * @param PdfPointStyle $style   the type of shape to draw
     *
     * @phpstan-param ColorStringType[] $legends
     */
    public function legendsHorizontal(
        array $legends,
        ?float $x = null,
        ?float $y = null,
        PdfPointStyle $style = PdfPointStyle::CIRCLE
    ): static {
        if ([] === $legends) {
            return $this;
        }

        $widths = $this->getLegendWidths($legends, $style);
        $totalWidth = $this->getLegendsWidth($legends, true);

        $y ??= $this->getY();
        $x ??= $this->getLeftMargin() + ($this->getPrintableWidth() - $totalWidth) / 2.0;

        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $index => $legend) {
            $this->outputLegend($style, $x, $y, $legend);
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
     * @param array         $legends the legends to draw
     * @param ?float        $x       the abscissa of the legends or null to use the current abscissa
     * @param ?float        $y       the ordinate of the legends or null to use the current ordinate
     * @param PdfPointStyle $style   the type of shape to draw
     *
     * @phpstan-param ColorStringType[] $legends
     */
    public function legendsVertical(
        array $legends,
        ?float $x = null,
        ?float $y = null,
        PdfPointStyle $style = PdfPointStyle::CIRCLE
    ): static {
        if ([] === $legends) {
            return $this;
        }

        $position = $this->getPosition();
        $x ??= $position->x;
        $y ??= $position->y;

        PdfDrawColor::cellBorder()->apply($this);
        foreach ($legends as $legend) {
            $this->outputLegend($style, $x, $y, $legend);
            $y += self::LINE_HEIGHT;
        }
        $this->resetStyle()
            ->setPosition($position);

        return $this;
    }

    /**
     * @phpstan-param ColorStringType $legend
     */
    private function applyLegendColor(array $legend, PdfPointStyle $shape): void
    {
        $color = $legend['color'];
        if (\is_string($color)) {
            $color = PdfRgbColor::create($color) ?? PdfRgbColor::darkGray();
        }
        $color = match ($shape) {
            PdfPointStyle::CROSS,
            PdfPointStyle::CROSS_ROTATION => PdfDrawColor::instance($color->red, $color->green, $color->blue),
            default => PdfFillColor::instance($color->red, $color->green, $color->blue),
        };
        $color->apply($this);
    }

    /**
     * @phpstan-param non-empty-array<ColorStringType> $legends
     *
     * @phpstan-return non-empty-array<float>
     */
    private function getLegendWidths(array $legends, PdfPointStyle $style): array
    {
        $offset = $this->getPointStyleWidth($style) + 2.0 * $this->getCellMargin();

        return \array_map(fn (array $legend): float => $offset + $this->getStringWidth($legend['label']), $legends);
    }

    /**
     * @phpstan-param ColorStringType $legend
     */
    private function outputLegend(
        PdfPointStyle $style,
        float $x,
        float $y,
        array $legend
    ): void {
        $this->applyLegendColor($legend, $style);
        $this->outputPointStyleAndText($style, $x, $y, $legend['label']);
    }
}
