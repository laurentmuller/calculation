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
 * Trait to draw dash border.
 */
trait PdfDashBorderTrait
{
    /**
     * Draw a dashed rectangle.
     *
     * @param float  $x1        the left position
     * @param float  $y1        the top position
     * @param float  $width     the rectangle width
     * @param float  $height    the rectangle height
     * @param int    $dashes    the number of dashes per line
     * @param ?float $lineWidth the line width or null to use current
     */
    public function dashedRect(float $x1, float $y1, float $width, float $height, int $dashes = 15, float $lineWidth = null): void
    {
        $oldWidth = null;
        if (null !== $lineWidth) {
            $oldWidth = $this->LineWidth;
            $this->SetLineWidth($lineWidth);
        }
        $x2 = $x1 + $width;
        $y2 = $y1 + $height;
        $increment = \max($width, $height) / (float) $dashes;
        $length = $increment / 2.0;
        // upper and lower dashes
        for ($x = $x1; $x <= $x2; $x += $increment) {
            $right = \min($x + $length, $x2 - 1.0);
            $this->Line($x, $y1, $right, $y1);
            $this->Line($x, $y2, $right, $y2);
        }
        // left and right dashes
        for ($y = $y1; $y <= $y2; $y += $increment) {
            $bottom = \min($y + $length, $y2 - 1.0);
            $this->Line($x1, $y, $x1, $bottom);
            $this->Line($x2, $y, $x2, $bottom);
        }
        if (null !== $oldWidth) {
            $this->SetLineWidth($oldWidth);
        }
    }

    /**
     * Draw a dashed rectangle.
     *
     * @param PdfRectangle $rectangle the rectangle to draw
     * @param int          $dashes    the number of dashes per line
     * @param ?float       $lineWidth the line width or null to use current
     */
    public function dashedRectangle(PdfRectangle $rectangle, int $dashes = 15, float $lineWidth = null): void
    {
        $this->dashedRect(
            $rectangle->x(),
            $rectangle->y(),
            $rectangle->width(),
            $rectangle->height(),
            $dashes,
            $lineWidth
        );
    }
}
