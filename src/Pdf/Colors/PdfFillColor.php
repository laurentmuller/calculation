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
 * RGB color used for filling operations (filled rectangles and cell backgrounds).
 */
readonly class PdfFillColor extends PdfRgbColor implements PdfDocumentUpdaterInterface
{
    #[\Override]
    public function apply(PdfDocument $doc): void
    {
        $doc->setFillColor($this);
    }

    /**
     * Gets the default fill color (white).
     */
    public static function default(): static
    {
        /** @phpstan-var static */
        return static::white();
    }

    /**
     * Gets the header fill color.
     *
     * The value is RGB(245, 245, 245).
     */
    public static function header(): self
    {
        return new self(245, 245, 245);
    }

    /**
     * Returns a value indicating if the fill color is set.
     *
     * To be true, this color must be different from white color.
     *
     * @return bool true if the fill color is set
     */
    public function isFillColor(): bool
    {
        return !$this->equals(static::white());
    }
}
