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

namespace App\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ExcelResponse represents an HTTP response within an Excel 2007 (.xlsx) document.
 *
 * @author Laurent Muller
 *
 * @see ExcelDocument
 */
class ExcelResponse extends StreamedResponse
{
    /**
     * Constructor.
     *
     * @param ExcelDocument $doc    the document to output
     * @param bool          $inline <code>true</code> to send the file inline to the browser. The Excel viewer is used if available.
     *                              <code>false</code> to send to the browser and force a file download with the name given.
     * @param string        $name   the name of the Excel file or null to use default ('document.xlsx')
     */
    public function __construct(ExcelDocument $doc, bool $inline = true, string $name = '')
    {
        $writer = IOFactory::createWriter($doc, 'Xlsx');

        $callback = function () use ($writer): void {
            $writer->save('php://output');
        };

        $headers = $doc->getOutputHeaders($inline, $name);

        parent::__construct($callback, self::HTTP_OK, $headers);
    }
}
