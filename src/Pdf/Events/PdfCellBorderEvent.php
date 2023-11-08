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

use App\Pdf\Interfaces\PdfDrawCellBorderInterface;
use App\Pdf\PdfBorder;
use App\Pdf\PdfDocument;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfTable;

/**
 * The event raised when a cell border must be drawn.
 *
 * @see PdfDrawCellBorderInterface
 */
readonly class PdfCellBorderEvent
{
    /**
     * @param PdfTable     $table  the parent's table
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
     * @param PdfBorder    $border the border style
     */
    public function __construct(
        public PdfTable $table,
        public int $index,
        public PdfRectangle $bounds,
        public PdfBorder $border
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
