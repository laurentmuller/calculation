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
use App\Spreadsheet\SpreadsheetDocument;
use App\Traits\ResponseTrait;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The SpreadsheetResponse represents an HTTP streamed response within an Excel 2007 (.xlsx) document.
 *
 * @author Laurent Muller
 *
 * @see SpreadsheetDocument
 */
class SpreadsheetResponse extends StreamedResponse implements IResponseInterface
{
    use ResponseTrait;
    /**
     * The application Microsoft Excel (OpenXML) mime type.
     */
    public const MIME_TYPE_EXCEL = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * Constructor.
     *
     * @param SpreadsheetDocument $doc    the document to output
     * @param bool                $inline <code>true</code> to send the file inline to the browser. The Spreasheet viewer is used if available.
     *                                    <code>false</code> to send to the browser and force a file download with the name given.
     * @param string              $name   the name of the document file or <code>''</code> to use the default name ('document.xlsx')
     */
    public function __construct(SpreadsheetDocument $doc, bool $inline = true, string $name = '')
    {
        $name = empty($name) ? 'document.xlsx' : \basename($name);
        $headers = $headers = $this->buildHeaders($name, self::MIME_TYPE_EXCEL, $inline);
        $callback = function () use ($doc): void {
            $writer = IOFactory::createWriter($doc, 'Xlsx');
            $writer->save('php://output');
        };
        parent::__construct($callback, self::HTTP_OK, $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeType(): string
    {
        return self::MIME_TYPE_EXCEL;
    }
}
