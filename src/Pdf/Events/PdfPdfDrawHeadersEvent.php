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

use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use fpdf\PdfDocument;

/**
 * The event raised when headers must be drawn.
 */
class PdfPdfDrawHeadersEvent
{
    /**
     * @param PdfTable $table       the parent's table
     * @param PdfStyle $headerStyle the header style
     */
    public function __construct(
        public PdfTable $table,
        public PdfStyle $headerStyle
    ) {
    }

    /**
     * Gets the table's columns.
     *
     * @return PdfColumn[]
     */
    public function getColumns(): array
    {
        return $this->table->getColumns();
    }

    /**
     * Gets the parent's document.
     */
    public function getDocument(): PdfDocument
    {
        return $this->table->getParent();
    }
}
