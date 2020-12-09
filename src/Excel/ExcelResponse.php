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

namespace App\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The ExcelResponse represents an HTTP streamed response within an Excel 2007 (.xlsx) document.
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
     * @param bool          $inline <code>true</code> to send the file inline to the browser. The Spreasheet viewer is used if available.
     *                              <code>false</code> to send to the browser and force a file download with the name given.
     * @param string        $name   the name of the document file or <code>''</code> to use the default name ('document.xlsx')
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
