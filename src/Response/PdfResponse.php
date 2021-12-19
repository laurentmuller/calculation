<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Response;

use App\Interfaces\IResponseInterface;
use App\Pdf\PdfDocument;
use App\Traits\ResponseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * The PdfResponse represents an HTTP response within a PDF document.
 *
 * @author Laurent Muller
 *
 * @see PdfDocument
 */
class PdfResponse extends Response implements IResponseInterface
{
    use ResponseTrait;
    /**
     * The application PDF mime type.
     */
    public const MIME_TYPE_PDF = 'application/pdf';

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
        $headers = $this->buildHeaders($name, self::MIME_TYPE_PDF, $inline);
        $content = $doc->Output(PdfDocument::OUTPUT_STRING);
        parent::__construct($content, self::HTTP_OK, $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeType(): string
    {
        return self::MIME_TYPE_PDF;
    }
}
