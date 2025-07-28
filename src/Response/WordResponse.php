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

use App\Word\WordDocument;
use PhpOffice\PhpWord\IOFactory;

/**
 * Represents an HTTP streamed response, within a Word 2007 (.docx) document.
 */
class WordResponse extends AbstractStreamedResponse
{
    /**
     * @param WordDocument $doc    the document to output
     * @param bool         $inline <code>true</code> to send the file inline to the browser.
     *                             The document viewer is used if available.
     *                             <code>false</code> to send to the browser and force a file download with the name
     *                             given.
     * @param string       $name   the name of the document file or <code>''</code> to use the default
     *                             name ('document.docx')
     */
    public function __construct(WordDocument $doc, bool $inline = true, string $name = '')
    {
        $callback = static fn (): null => IOFactory::createWriter($doc)->save('php://output');
        parent::__construct($callback, $inline, $name);
    }

    #[\Override]
    public function getFileExtension(): string
    {
        return 'docx';
    }
}
