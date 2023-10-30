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
 * Trait to draw dash lines.
 */
trait PdfDashLineTrait
{
    /**
     * Draw a dashed rectangle.
     *
     * @param float              $x      the abscissa of upper-left corner
     * @param float              $y      the ordinate of upper-left corner
     * @param float              $w      the width
     * @param float              $h      the height
     * @param int                $dashes the number of dashes per line
     * @param PdfLine|float|null $line   the line width or null to use current
     */
    public function dashedRect(
        float $x,
        float $y,
        float $w,
        float $h,
        int $dashes = 15,
        PdfLine|float $line = null
    ): void {
        $oldWidth = $this->LineWidth;
        if ($line instanceof PdfLine) {
            $line->apply($this);
        } elseif (\is_float($line)) {
            $this->SetLineWidth($line);
        }

        $right = $x + $w;
        $bottom = $y + $h;
        $increment = \max($w, $h) / (float) $dashes;
        $length = $increment / 2.0;

        // upper and lower dashes
        $endValue = $right - 1.0;
        for ($currentX = $x; $currentX <= $right; $currentX += $increment) {
            $end = \min($currentX + $length, $endValue);
            $this->Line($currentX, $y, $end, $y);
            $this->Line($currentX, $bottom, $end, $bottom);
        }

        // left and right dashes
        $endValue = $bottom - 1.0;
        for ($currentY = $y; $currentY <= $bottom; $currentY += $increment) {
            $end = \min($currentY + $length, $endValue);
            $this->Line($x, $currentY, $x, $end);
            $this->Line($right, $currentY, $right, $end);
        }

        $this->SetLineWidth($oldWidth);
    }

    /**
     * Draw a dashed rectangle.
     *
     * @param PdfRectangle       $rectangle the rectangle to draw
     * @param int                $dashes    the number of dashes per line
     * @param PdfLine|float|null $line      the line width or null to use current
     */
    public function dashedRectangle(PdfRectangle $rectangle, int $dashes = 15, PdfLine|float $line = null): void
    {
        $this->dashedRect(
            $rectangle->x(),
            $rectangle->y(),
            $rectangle->width(),
            $rectangle->height(),
            $dashes,
            $line
        );
    }

    /**
     * Set the dash pattern to draw dashed lines or rectangles.
     *
     * Call the function without parameter to restore normal drawing.
     *
     * @param float|null $black the length of dashes
     * @param float|null $white the length of gaps
     */
    public function setDashPattern(float $black = null, float $white = null): void
    {
        if (null !== $black && null !== $white) {
            $this->_outParams('[%.3F %.3F] 0 d', $black * $this->k, $white * $this->k);
        } else {
            $this->_out('[] 0 d');
        }
    }
}
