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
use App\Traits\MimeTypeTrait;
use App\Word\WordDocument;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The WordResponse represents an HTTP streamed response within a Word 2007 (.docx) document.
 */
class WordResponse extends StreamedResponse implements MimeTypeInterface
{
    use MimeTypeTrait;

    /**
     * @param WordDocument $doc    the document to output
     * @param bool         $inline <code>true</code> to send the file inline to the browser. The document viewer is used if available.
     *                             <code>false</code> to send to the browser and force a file download with the name given.
     * @param string       $name   the name of the document file or <code>''</code> to use the default name ('document.docx')
     */
    public function __construct(WordDocument $doc, bool $inline = true, string $name = '')
    {
        $callback = function () use ($doc): void {
            $writer = IOFactory::createWriter($doc);
            $writer->save('php://output');
        };
        $headers = $this->buildHeaders($name, $inline);
        parent::__construct(callback: $callback, headers: $headers);
    }

    public function getFileExtension(): string
    {
        return 'docx';
    }
}
