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

/**
 * The CsvResponse represents an HTTP streamed response within an CSV content.
 */
class CsvResponse extends AbstractStreamedResponse
{
    /**
     * @param callable|null $callback the callback to output content
     * @param bool          $inline   <code>true</code> to send the file inline to the browser. The document viewer is
     *                                used if available. <code>false</code> to send to the browser and force a file
     *                                download with the name given.
     * @param string        $name     the name of the document file or <code>''</code> to use the default
     *                                name ('document.csv')
     */
    public function __construct(?callable $callback = null, bool $inline = true, string $name = '')
    {
        parent::__construct($callback, $inline, $name);
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }
}
