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
use App\Util\Response\ResponseUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The CsvResponse represents an HTTP streamed response within an CSV content.
 *
 * @author Laurent Muller
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CsvResponse extends StreamedResponse implements IResponseInterface
{
    /**
     * The CSV mime type.
     */
    final public const MIME_TYPE_CSV = 'text/csv';

    /**
     * Constructor.
     *
     * @param callable|null $callback the callback to output content
     * @param bool          $inline   <code>true</code> to send the file inline to the browser. The CSV viewer is used if available.
     *                                <code>false</code> to send to the browser and force a file download with the name given.
     * @param string        $name     the name of the document file or <code>''</code> to use the default name ('document.csv')
     */
    public function __construct(callable $callback = null, bool $inline = true, string $name = '')
    {
        $name = empty($name) ? 'document.csv' : \basename($name);
        $headers = ResponseUtils::buildHeaders($name, self::MIME_TYPE_CSV, $inline);
        parent::__construct($callback, self::HTTP_OK, $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeType(): string
    {
        return self::MIME_TYPE_CSV;
    }
}
