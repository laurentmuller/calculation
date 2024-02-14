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

use App\Pdf\PdfBorder;
use fpdf\PdfRectangleStyle;

/**
 * Trait to draw circles and ellipses.
 *
 * @psalm-require-extends \App\Pdf\PdfDocument
 */
trait PdfEllipseTrait
{
    /**
     * Draw a circle.
     *
     * @param float                       $x     the abscissa position
     * @param float                       $y     the ordinate position
     * @param float                       $r     the radius
     * @param PdfBorder|PdfRectangleStyle $style the style of rendering. Possible values are:
     *                                           <ul>
     *                                           <li>A PdfBorder instance.</li>
     *                                           <li>A PdfRectangleStyle enumeration.</li>
     *                                           </ul>
     */
    public function circle(
        float $x,
        float $y,
        float $r,
        PdfBorder|PdfRectangleStyle $style = PdfRectangleStyle::BORDER
    ): void {
        $this->ellipse($x, $y, $r, $r, $style);
    }

    /**
     * Draw an ellipse.
     *
     * @param float                       $x     the abscissa position
     * @param float                       $y     the ordinate position
     * @param float                       $rx    the horizontal radius
     * @param float                       $ry    the vertical radius
     * @param PdfBorder|PdfRectangleStyle $style the style of rendering. Possible values are:
     *                                           <ul>
     *                                           <li>A PdfBorder instance.</li>
     *                                           <li>A PdfRectangleStyle enumeration.</li>
     *                                           </ul>
     */
    public function ellipse(
        float $x,
        float $y,
        float $rx,
        float $ry,
        PdfBorder|PdfRectangleStyle $style = PdfRectangleStyle::BORDER
    ): void {
        if ($style instanceof PdfBorder) {
            $style = $style->getRectangleStyle();
            if (!$style instanceof PdfRectangleStyle) {
                return;
            }
        }
        $lx = 4.0 / 3.0 * (\M_SQRT2 - 1.0) * $rx;
        $ly = 4.0 / 3.0 * (\M_SQRT2 - 1.0) * $ry;
        $scaleFactor = $this->scaleFactor;
        $height = $this->height;

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
