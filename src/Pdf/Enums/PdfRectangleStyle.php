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

namespace App\Pdf\Enums;

use App\Pdf\PdfDocument;

/**
 * The PDF style to draw and/or fill rectangle.
 *
 * @see PdfDocument::Rect()
 * @see PdfDocument::rectangle()
 */
enum PdfRectangleStyle: string
{
    /*
     * Draw the border around the rectangle.
     */
    case BORDER = 'D';
    /*
     * Draw the border and fill the rectangle.
     */
    case BOTH = 'FD';
    /*
     * Fill the rectangle.
     */
    case FILL = 'F';
    /*
     * No border is draw, nor fill.
     */
    case NONE = '';
    /**
     * Return a value indicating if the fill or/and draw rectangle can be applied.
     */
    public function isApplicable(): bool
    {
        return self::NONE !== $this;
    }
}
