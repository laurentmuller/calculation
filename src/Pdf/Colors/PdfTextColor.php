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
 * RGB color used for drawing text operations.
 */
readonly class PdfTextColor extends PdfRgbColor implements PdfDocumentUpdaterInterface
{
    #[\Override]
    public function apply(PdfDocument $doc): void
    {
        $doc->setTextColor($this);
    }

    /**
     * Gets the default text color (black).
     */
    public static function default(): static
    {
        return static::black();
    }
}
