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

use App\Pdf\Interfaces\PdfOutputHeadersListener;
use App\Pdf\PdfDocument;
use App\Pdf\PdfTable;

/**
 * The event raised when headers are drawn.
 *
 * @see PdfOutputHeadersListener
 */
readonly class PdfHeadersEvent
{
    /**
     * @param PdfTable $table the parent's table
     * @param bool     $start true if starting output headers; false if ending
     */
    public function __construct(public PdfTable $table, public bool $start)
    {
    }

    /**
     * Gets the parent's document.
     */
    public function getDocument(): PdfDocument
    {
        return $this->table->getParent();
    }
}
