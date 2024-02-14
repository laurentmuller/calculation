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

use App\Pdf\PdfDocument;

/**
 * Color used color for filling operations (filled rectangles and cell backgrounds).
 */
class PdfFillColor extends AbstractPdfColor
{
    public function apply(PdfDocument $doc): void
    {
        $doc->setFillColor($this->red, $this->green, $this->blue);
    }

    /**
     * The default fill color is white.
     */
    public static function default(): self
    {
        return self::white();
    }

    /**
     * Gets a value indicating if the fill color is set.
     *
     * To be true, this color must be different from the white color.
     *
     * @return bool true if the fill color is set
     */
    public function isFillColor(): bool
    {
        return self::MAX_VALUE !== $this->red
            || self::MAX_VALUE !== $this->green
            || self::MAX_VALUE !== $this->blue;
    }
}
