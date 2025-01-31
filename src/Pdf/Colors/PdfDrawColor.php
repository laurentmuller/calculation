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

use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;
use fpdf\Color\PdfRgbColor;
use fpdf\PdfDocument;

/**
 * RGB color used for all drawing operations (lines, rectangles and for cell borders).
 */
readonly class PdfDrawColor extends PdfRgbColor implements PdfDocumentUpdaterInterface
{
    public function apply(PdfDocument $doc): void
    {
        $doc->setDrawColor($this);
    }

    /**
     * Gets the cell border color.
     *
     * The value is RGB(221, 221, 221).
     */
    public static function cellBorder(): self
    {
        return new self(221, 221, 221);
    }

    /**
     * Gets the default draw color (black).
     */
    public static function default(): static
    {
        /** @psalm-var static */
        return static::black();
    }
}
