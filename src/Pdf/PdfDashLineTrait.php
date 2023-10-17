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
     * @param float              $x      the left position
     * @param float              $y      the top position
     * @param float              $width  the rectangle width
     * @param float              $height the rectangle height
     * @param int                $dashes the number of dashes per line
     * @param PdfLine|float|null $line   the line width or null to use current
     */
    public function dashedRect(
        float $x,
        float $y,
        float $width,
        float $height,
        int $dashes = 15,
        PdfLine|float $line = null
    ): void {
        $oldWidth = $this->LineWidth;
        if ($line instanceof PdfLine) {
            $line->apply($this);
        } elseif (\is_float($line)) {
            $this->SetLineWidth($line);
        }

        $x2 = $x + $width;
        $y2 = $y + $height;
        $increment = \max($width, $height) / (float) $dashes;
        $length = $increment / 2.0;

        // upper and lower dashes
        $lastValue = $x2 - 1.0;
        for ($currentX = $x; $currentX <= $x2; $currentX += $increment) {
            $right = \min($currentX + $length, $lastValue);
            $this->Line($currentX, $y, $right, $y);
            $this->Line($currentX, $y2, $right, $y2);
        }

        // left and right dashes
        $lastValue = $y2 - 1.0;
        for ($currentY = $y; $currentY <= $y2; $currentY += $increment) {
            $bottom = \min($currentY + $length, $lastValue);
            $this->Line($x, $currentY, $x, $bottom);
            $this->Line($x2, $currentY, $x2, $bottom);
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
     * Set a dash pattern for draw dashed lines or rectangles.
     *
     * Call the function without parameter to restore normal drawing.
     *
     * @param float|null $black the length of dashes
     * @param float|null $white the length of gaps
     */
    public function setDash(float $black = null, float $white = null): void
    {
        if (null !== $black && null !== $white) {
            $this->_out(\sprintf('[%.3F %.3F] 0 d', $black * $this->k, $white * $this->k));
        } else {
            $this->_out('[] 0 d');
        }
    }
}
