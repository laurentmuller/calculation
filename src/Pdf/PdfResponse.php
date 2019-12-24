<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pdf;

use Symfony\Component\HttpFoundation\Response;

/**
 * PdfResponse represents an HTTP response within a PDF document.
 *
 * @author Laurent Muller
 *
 * @see PdfDocument
 */
class PdfResponse extends Response
{
    /**
     * Constructor.
     *
     * @param PdfDocument $doc    the document to output
     * @param bool        $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if available.
     *                            <code>false</code> to send to the browser and force a file download with the name given.
     * @param string      $name   the name of the file. The default value is <code>doc.pdf</code>.
     * @param bool        $isUTF8 indicates if name is encoded in ISO-8859-1 (false) or UTF-8 (true)
     */
    public function __construct(PdfDocument $doc, bool $inline = true, string $name = '', bool $isUTF8 = false)
    {
        // get content and headers
        $content = $doc->Output(PdfDocument::OUTPUT_STRING);
        $headers = $doc->getOutputHeaders($inline, $name, $isUTF8);

        // contructor
        parent::__construct($content, self::HTTP_OK, $headers);
    }
}
