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
 * Color used color for all drawing operations (lines, rectangles and cell borders).
 */
class PdfDrawColor extends AbstractPdfColor
{
    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $doc->SetDrawColor($this->red, $this->green, $this->blue);
    }
}
