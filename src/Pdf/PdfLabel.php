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

use fpdf\Enums\PdfPageSize;
use fpdf\Enums\PdfUnit;

/**
 * Contains label layout.
 */
class PdfLabel
{
    /**
     * The number of horizontal labels (columns).
     *
     * @phpstan-var positive-int
     */
    public int $cols = 1;
    /**
     * The font size in points.
     *
     * @phpstan-var positive-int
     */
    public int $fontSize = 9;
    /**
     * The height of labels.
     */
    public float $height = 0.0;
    /**
     * The left margin.
     */
    public float $marginLeft = 0.0;
    /**
     * The top margin.
     */
    public float $marginTop = 0.0;
    /**
     * The label's name.
     */
    public string $name = '';
    /**
     * The page size.
     */
    public PdfPageSize $pageSize = PdfPageSize::A4;
    /**
     * The number of vertical labels (rows).
     *
     * @phpstan-var positive-int
     */
    public int $rows = 1;
    /**
     * The horizontal space between 2 labels.
     */
    public float $spaceX = 0.0;
    /**
     * The vertical space between 2 labels.
     */
    public float $spaceY = 0.0;
    /**
     * The layout unit.
     */
    public PdfUnit $unit = PdfUnit::MILLIMETER;
    /**
     * The width of labels.
     */
    public float $width = 0.0;

    /**
     * Gets the horizontal offset for the given column.
     */
    public function getOffsetX(int $column): float
    {
        return $this->marginLeft + (float) $column * ($this->width + $this->spaceX);
    }

    /**
     * Gets the vertical offset for the given row.
     */
    public function getOffsetY(int $row): float
    {
        return $this->marginTop + (float) $row * ($this->height + $this->spaceY);
    }

    /**
     * Clone this instance and convert values to millimeters.
     *
     * Returns this instance if this unit is already set as millimeter.
     *
     * The returned instance has the unit set to the millimeter.
     */
    public function scaleToMillimeters(): self
    {
        if (PdfUnit::MILLIMETER === $this->unit) {
            return $this;
        }

        $factor = $this->getScaleFactor();

        $copy = clone $this;
        $copy->unit = PdfUnit::MILLIMETER;
        $copy->marginLeft *= $factor;
        $copy->marginTop *= $factor;
        $copy->spaceX *= $factor;
        $copy->spaceY *= $factor;
        $copy->width *= $factor;
        $copy->height *= $factor;

        return $copy;
    }

    /**
     * Gets the number of labels for a page.
     */
    public function size(): int
    {
        return $this->cols * $this->rows;
    }

    private function getScaleFactor(): float
    {
        return $this->unit->getScaleFactor() / PdfUnit::MILLIMETER->getScaleFactor();
    }
}
