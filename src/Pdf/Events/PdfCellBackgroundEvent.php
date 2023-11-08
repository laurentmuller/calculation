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

use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\PdfDocument;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfTable;

/**
 * The event raised when a cell background must be drawn.
 *
 * @see PdfDrawCellBackgroundInterface
 */
readonly class PdfCellBackgroundEvent
{
    /**
     * @param PdfTable     $table  the parent's table
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
     */
    public function __construct(
        public PdfTable $table,
        public int $index,
        public PdfRectangle $bounds
    ) {
    }

    /**
     * Gets the parent's document.
     */
    public function getParent(): PdfDocument
    {
        return $this->table->getParent();
    }
}
