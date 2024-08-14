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

use App\Pdf\PdfLine;
use fpdf\PdfRectangle;

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
        PdfLine|float|null $line = null
    ): void {
        $oldWidth = $this->lineWidth;
        if ($line instanceof PdfLine) {
            $line->apply($this);
        } elseif (\is_float($line)) {
            $this->setLineWidth($line);
        }

        $right = $x + $w;
        $bottom = $y + $h;
        $increment = \max($w, $h) / (float) $dashes;
        $length = $increment / 2.0;

        // upper and lower dashes
        $endValue = $right - 1.0;
        for ($currentX = $x; $currentX <= $right; $currentX += $increment) {
            $end = \min($currentX + $length, $endValue);
            $this->line($currentX, $y, $end, $y);
            $this->line($currentX, $bottom, $end, $bottom);
        }

        // left and right dashes
        $endValue = $bottom - 1.0;
        for ($currentY = $y; $currentY <= $bottom; $currentY += $increment) {
            $end = \min($currentY + $length, $endValue);
            $this->line($x, $currentY, $x, $end);
            $this->line($right, $currentY, $right, $end);
        }

        $this->setLineWidth($oldWidth);
    }

    /**
     * Draw a dashed rectangle.
     *
     * @param PdfRectangle       $rectangle the rectangle to draw
     * @param int                $dashes    the number of dashes per line
     * @param PdfLine|float|null $line      the line width or null to use current
     */
    public function dashedRectangle(PdfRectangle $rectangle, int $dashes = 15, PdfLine|float|null $line = null): void
    {
        $this->dashedRect(
            $rectangle->x,
            $rectangle->y,
            $rectangle->width,
            $rectangle->height,
            $dashes,
            $line
        );
    }

    /**
     * Reset the dash pattern.
     */
    public function resetDashPattern(): static
    {
        $this->out('[] 0 d');

        return $this;
    }

    /**
     * Set the dash pattern used to draw dashed lines or rectangles.
     *
     * @param float $black the length of dashes
     * @param float $white the length of gaps
     */
    public function setDashPattern(float $black, float $white): static
    {
        $this->outf('[%.3F %.3F] 0 d', $black * $this->scaleFactor, $white * $this->scaleFactor);

        return $this;
    }
}
