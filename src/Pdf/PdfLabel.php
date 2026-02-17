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
readonly class PdfLabel implements \Stringable
{
    /**
     * @param string       $name       the label's name
     * @param PdfUnit      $unit       the layout unit
     * @param positive-int $cols       the number of horizontal labels (columns)
     * @param positive-int $rows       the number of vertical labels (rows)
     * @param float        $width      the width of a label
     * @param float        $height     the height of a label
     * @param float        $marginLeft the left margin
     * @param float        $marginTop  the top margin
     * @param float        $spaceX     the horizontal space between labels
     * @param float        $spaceY     the vertical space between labels
     * @param int<6, 15>   $fontSize   the font size in points
     * @param PdfPageSize  $pageSize   the page size
     */
    public function __construct(
        public string $name,
        public int $cols,
        public int $rows,
        public float $width,
        public float $height,
        public float $marginLeft = 0.0,
        public float $marginTop = 0.0,
        public float $spaceX = 0.0,
        public float $spaceY = 0.0,
        public int $fontSize = 9,
        public PdfUnit $unit = PdfUnit::MILLIMETER,
        public PdfPageSize $pageSize = PdfPageSize::A4
    ) {
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Creates a copy of this instance with the desired new values.
     *
     * @param positive-int|null $cols     the number of horizontal labels (columns)
     * @param positive-int|null $rows     the number of vertical labels (rows)
     * @param int<6, 15>|null   $fontSize the font size in points
     */
    public function copy(
        ?string $name = null,
        ?int $cols = null,
        ?int $rows = null,
        ?float $width = null,
        ?float $height = null,
        ?float $marginLeft = null,
        ?float $marginTop = null,
        ?float $spaceX = null,
        ?float $spaceY = null,
        ?int $fontSize = null,
        ?PdfUnit $unit = null,
        ?PdfPageSize $pageSize = null
    ): self {
        return new self(
            name: $name ?? $this->name,
            cols: $cols ?? $this->cols,
            rows: $rows ?? $this->rows,
            width: $width ?? $this->width,
            height: $height ?? $this->height,
            marginLeft: $marginLeft ?? $this->marginLeft,
            marginTop: $marginTop ?? $this->marginTop,
            spaceX: $spaceX ?? $this->spaceX,
            spaceY: $spaceY ?? $this->spaceY,
            fontSize: $fontSize ?? $this->fontSize,
            unit: $unit ?? $this->unit,
            pageSize: $pageSize ?? $this->pageSize
        );
    }

    /**
     * Gets the horizontal offset for the given column.
     */
    public function offsetX(int $column): float
    {
        return $this->marginLeft + (float) $column * ($this->width + $this->spaceX);
    }

    /**
     * Gets the vertical offset for the given row.
     */
    public function offsetY(int $row): float
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
        return $this->scaleToUnit(PdfUnit::MILLIMETER);
    }

    /**
     * Clone this instance and convert values to the given target unit.
     *
     * Returns this instance if this unit is the same as the given target unit.
     */
    public function scaleToUnit(PdfUnit $targetUnit): self
    {
        if ($targetUnit === $this->unit) {
            return $this;
        }

        $factor = $this->unit->getScaleFactor() / $targetUnit->getScaleFactor();

        return new self(
            name: $this->name,
            cols: $this->cols,
            rows: $this->rows,
            width: $this->width * $factor,
            height: $this->height * $factor,
            marginLeft: $this->marginLeft * $factor,
            marginTop: $this->marginTop * $factor,
            spaceX: $this->spaceX * $factor,
            spaceY: $this->spaceY * $factor,
            fontSize: $this->fontSize,
            unit: $targetUnit,
            pageSize: $this->pageSize
        );
    }

    /**
     * Gets the number of labels for a page.
     */
    public function size(): int
    {
        return $this->cols * $this->rows;
    }
}
