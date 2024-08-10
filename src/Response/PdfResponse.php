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

namespace App\Response;

use fpdf\Enums\PdfDestination;
use fpdf\PdfDocument;

/**
 * The PdfResponse represents an HTTP response within a PDF document.
 *
 * @see PdfDocument
 */
class PdfResponse extends AbstractStreamedResponse
{
    /**
     * @param PdfDocument $doc    the document to output
     * @param bool        $inline <code>true</code> to send the file inline to the browser.
     *                            The document viewer is used if available.
     *                            <code>false</code> to send to the browser and force a file download with the name
     *                            given.
     * @param string      $name   the name of the document file or <code>''</code> to use the default name
     *                            ('document.pdf').
     */
    public function __construct(PdfDocument $doc, bool $inline = true, string $name = '')
    {
        $callback = fn (): string => $doc->output(PdfDestination::FILE, 'php://output');
        parent::__construct($callback, $inline, $name);
    }

    public function getFileExtension(): string
    {
        return 'pdf';
    }
}
