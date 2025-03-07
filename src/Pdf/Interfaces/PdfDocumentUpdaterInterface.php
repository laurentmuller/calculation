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

use fpdf\PdfDocument;

/**
 * Class implementing this interface applies properties to a PdfDocument.
 */
interface PdfDocumentUpdaterInterface
{
    /**
     * Apply changes to the given document.
     */
    public function apply(PdfDocument $doc): void;
}
