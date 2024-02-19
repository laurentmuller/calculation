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

namespace App\Pdf\Events;

use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\PdfDocument;
use App\Pdf\PdfTable;
use fpdf\PdfRectangle;
use fpdf\PdfTextAlignment;

/**
 * The event raised when a cell text must be drawn.
 *
 * @see PdfDrawCellTextInterface
 */
readonly class PdfCellTextEvent
{
    /**
     * @param PdfTable         $table  the parent's table
     * @param int              $index  the column index
     * @param PdfRectangle     $bounds the cell bounds
     * @param string           $text   the cell text
     * @param PdfTextAlignment $align  the text alignment
     * @param float            $height the line height
     */
    public function __construct(
        public PdfTable $table,
        public int $index,
        public PdfRectangle $bounds,
        public string $text,
        public PdfTextAlignment $align,
        public float $height
    ) {
    }

    /**
     * Gets the parent's document.
     */
    public function getDocument(): PdfDocument
    {
        return $this->table->getParent();
    }
}
