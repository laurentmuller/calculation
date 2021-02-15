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

use App\Util\Utils;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\HeaderUtils;
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
     * The application download type.
     */
    public const MIME_TYPE_DOWNLOAD = 'application/x-download';

    /**
     * The application Microsoft Excel (OpenXML) mime type.
     */
    public const MIME_TYPE_EXCEL = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

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
        $name = empty($name) ? 'document.xlsx' : \basename($name);
        $encoded = Utils::ascii($name);

        if ($inline) {
            $type = self::MIME_TYPE_EXCEL;
            $disposition = HeaderUtils::DISPOSITION_INLINE;
        } else {
            $type = self::MIME_TYPE_DOWNLOAD;
            $disposition = HeaderUtils::DISPOSITION_ATTACHMENT;
        }

        $headers = [
            'Pragma' => 'public',
            'Content-Type' => $type,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name, $encoded),
        ];

        $callback = function () use ($doc): void {
            $writer = IOFactory::createWriter($doc, 'Xlsx');
            $writer->save('php://output');
        };

        parent::__construct($callback, self::HTTP_OK, $headers);
    }
}
