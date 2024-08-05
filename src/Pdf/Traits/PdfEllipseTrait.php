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

use fpdf\PdfRectangleStyle;

/**
 * Trait to draw circles and ellipses.
 *
 * @psalm-require-extends \fpdf\PdfDocument
 */
trait PdfEllipseTrait
{
    /**
     * Draw a circle.
     *
     * It can be drawn (border only), filled (with no border) or both.
     *
     * @param float             $x     the abscissa of the center
     * @param float             $y     the ordinate of the center
     * @param float             $r     the radius
     * @param PdfRectangleStyle $style the style of rendering
     */
    public function circle(
        float $x,
        float $y,
        float $r,
        PdfRectangleStyle $style = PdfRectangleStyle::BORDER
    ): void {
        $this->ellipse($x, $y, $r, $r, $style);
    }

    /**
     * Draw an ellipse.
     *
     * It can be drawn (border only), filled (with no border) or both.
     *
     * @param float             $x     the abscissa of the center
     * @param float             $y     the ordinate of the center
     * @param float             $rx    the horizontal radius
     * @param float             $ry    the vertical radius
     * @param PdfRectangleStyle $style the style of rendering
     */
    public function ellipse(
        float $x,
        float $y,
        float $rx,
        float $ry,
        PdfRectangleStyle $style = PdfRectangleStyle::BORDER
    ): void {
        $height = $this->height;
        $scaleFactor = $this->scaleFactor;
        $lx = 4.0 / 3.0 * (\M_SQRT2 - 1.0) * $rx;
        $ly = 4.0 / 3.0 * (\M_SQRT2 - 1.0) * $ry;

        $this->outf(
            '%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $rx) * $scaleFactor,
            ($height - $y) * $scaleFactor,
            ($x + $rx) * $scaleFactor,
            ($height - ($y - $ly)) * $scaleFactor,
            ($x + $lx) * $scaleFactor,
            ($height - ($y - $ry)) * $scaleFactor,
            $x * $scaleFactor,
            ($height - ($y - $ry)) * $scaleFactor
        );
        $this->outf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $lx) * $scaleFactor,
            ($height - ($y - $ry)) * $scaleFactor,
            ($x - $rx) * $scaleFactor,
            ($height - ($y - $ly)) * $scaleFactor,
            ($x - $rx) * $scaleFactor,
            ($height - $y) * $scaleFactor
        );
        $this->outf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $rx) * $scaleFactor,
            ($height - ($y + $ly)) * $scaleFactor,
            ($x - $lx) * $scaleFactor,
            ($height - ($y + $ry)) * $scaleFactor,
            $x * $scaleFactor,
            ($height - ($y + $ry)) * $scaleFactor
        );
        $this->outf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x + $lx) * $scaleFactor,
            ($height - ($y + $ry)) * $scaleFactor,
            ($x + $rx) * $scaleFactor,
            ($height - ($y + $ly)) * $scaleFactor,
            ($x + $rx) * $scaleFactor,
            ($height - $y) * $scaleFactor,
            $style->value
        );
    }
}
