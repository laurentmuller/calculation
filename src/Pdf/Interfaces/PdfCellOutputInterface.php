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

namespace App\Pdf\Interfaces;

use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\PdfRectangle;

/**
 * Class implementing this interface output content to a PdfDocument.
 */
interface PdfCellOutputInterface
{
    /**
     * Output this content to the given parent.
     *
     * @param PdfDocument       $parent    the parent to output content to
     * @param PdfRectangle      $bounds    the cell bounds
     * @param ?PdfTextAlignment $alignment the text alignment
     * @param PdfMove           $move      indicates where the current position should go after the call
     *
     * @see PdfDocument::cell()
     */
    public function output(
        PdfDocument $parent,
        PdfRectangle $bounds,
        ?PdfTextAlignment $alignment = null,
        PdfMove $move = PdfMove::RIGHT
    ): void;
}
