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

namespace App\Pdf;

/**
 * Class implementing this interface applies some changes to a <code>PdfDocument</code>.
 *
 * @author Laurent Muller
 */
interface PdfDocumentUpdaterInterface
{
    /**
     * Apply changes to the given document.
     *
     * @param PdfDocument $doc The document to update
     */
    public function apply(PdfDocument $doc): void;
}
