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

namespace App\Pdf\Colors;

use fpdf\Color\PdfRgbColor;
use fpdf\PdfDocument;

/**
 * Color used for drawing text operations.
 */
class PdfTextColor extends AbstractPdfColor
{
    public function apply(PdfDocument $doc): void
    {
        $color = PdfRgbColor::instance($this->red, $this->green, $this->blue);
        $doc->setTextColor($color);
    }

    /**
     * The default draw color is black.
     */
    public static function default(): self
    {
        return self::black();
    }
}
