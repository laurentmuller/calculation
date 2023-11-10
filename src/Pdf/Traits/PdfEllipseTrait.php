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

use App\Pdf\Enums\PdfRectangleStyle;
use App\Pdf\PdfBorder;

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
     * @param float                              $x     the abscissa position
     * @param float                              $y     the ordinate position
     * @param float                              $r     the radius
     * @param PdfBorder|PdfRectangleStyle|string $style the style of rendering. Possible values are:
     *                                                  <ul>
     *                                                  <li>A PdfBorder instance.</li>
     *                                                  <li>A PdfRectangleStyle enumeration.</li>
     *                                                  <li><code>'D'</code> or an empty string (""): Draw (default value).</li>
     *                                                  <li><code>'F'</code>: Fill.</li>
     *                                                  <li><code>'DF'</code>: Draw and fill.</li>
     *                                                  </ul>
     */
    public function circle(float $x, float $y, float $r, PdfBorder|PdfRectangleStyle|string $style = 'D'): void
    {
        $this->ellipse($x, $y, $r, $r, $style);
    }

    /**
     * Draw an ellipse.
     *
     * @param float                              $x     the abscissa position
     * @param float                              $y     the ordinate position
     * @param float                              $rx    the horizontal radius
     * @param float                              $ry    the vertical radius
     * @param PdfBorder|PdfRectangleStyle|string $style the style of rendering. Possible values are:
     *                                                  <ul>
     *                                                  <li>A PdfBorder instance.</li>
     *                                                  <li>A PdfRectangleStyle enumeration.</li>
     *                                                  <li><code>'D'</code> or an empty string (""): Draw (default value).</li>
     *                                                  <li><code>'F'</code>: Fill.</li>
     *                                                  <li><code>'DF'</code>: Draw and fill.</li>
     *                                                  </ul>
     */
    public function ellipse(float $x, float $y, float $rx, float $ry, PdfBorder|PdfRectangleStyle|string $style = 'D'): void
    {
        if ($style instanceof PdfBorder) {
            if (!$style->isRectangleStyle()) {
                return;
            }
            $style = $style->getRectangleStyle();
        }
        if ($style instanceof PdfRectangleStyle) {
            if (!$style->isApplicable()) {
                return;
            }
            $style = $style->value;
        }
        $style = \strtoupper($style);
        if ('F' === $style) {
            $op = 'f';
        } elseif ('FD' === $style || 'DF' === $style) {
            $op = 'B';
        } else {
            $op = 'S';
        }

        $lx = 4.0 / 3.0 * (\M_SQRT2 - 1.0) * $rx;
        $ly = 4.0 / 3.0 * (\M_SQRT2 - 1.0) * $ry;
        $k = $this->k;
        $h = $this->h;

        $this->_outParams(
            '%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $rx) * $k,
            ($h - $y) * $k,
            ($x + $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x + $lx) * $k,
            ($h - ($y - $ry)) * $k,
            $x * $k,
            ($h - ($y - $ry)) * $k
        );
        $this->_outParams(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $lx) * $k,
            ($h - ($y - $ry)) * $k,
            ($x - $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x - $rx) * $k,
            ($h - $y) * $k
        );
        $this->_outParams(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x - $lx) * $k,
            ($h - ($y + $ry)) * $k,
            $x * $k,
            ($h - ($y + $ry)) * $k
        );
        $this->_outParams(
            '%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x + $lx) * $k,
            ($h - ($y + $ry)) * $k,
            ($x + $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x + $rx) * $k,
            ($h - $y) * $k,
            $op
        );
    }
}
