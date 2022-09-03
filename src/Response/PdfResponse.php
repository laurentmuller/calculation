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

use App\Interfaces\MimeTypeInterface;
use App\Pdf\Enums\PdfDocumentOutput;
use App\Pdf\PdfDocument;
use App\Traits\MimeTypeTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * The PdfResponse represents an HTTP response within a PDF document.
 *
 * @see PdfDocument
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PdfResponse extends Response implements MimeTypeInterface
{
    use MimeTypeTrait;

    /**
     * Constructor.
     *
     * @param PdfDocument $doc    the document to output
     * @param bool        $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if available.
     *                            <code>false</code> to send to the browser and force a file download with the name given.
     * @param string      $name   the name of the document file or <code>''</code> to use the default name ('document.pdf').
     */
    public function __construct(PdfDocument $doc, bool $inline = true, string $name = '')
    {
        $name = empty($name) ? 'document.pdf' : \basename($name);
        $headers = $this->buildHeaders($name, $inline);
        $content = $doc->Output(PdfDocumentOutput::STRING);
        parent::__construct($content, self::HTTP_OK, $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeType(): string
    {
        return 'application/pdf';
    }
}
