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

use fpdf\PdfDocument;

/**
 * Color used color for all drawing operations (lines, rectangles and cell borders).
 */
class PdfDrawColor extends AbstractPdfColor
{
    public function apply(PdfDocument $doc): void
    {
        $doc->setDrawColor($this->red, $this->green, $this->blue);
    }

    /**
     * The default draw color is black.
     */
    public static function default(): self
    {
        return self::black();
    }
}
