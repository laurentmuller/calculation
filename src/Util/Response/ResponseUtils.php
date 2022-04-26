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

namespace App\Util\Response;

use App\Util\Utils;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Utility class for response headers.
 */
class ResponseUtils
{
    /**
     * Build response headers for an attachment.
     *
     * @param string $name     the document name
     * @param string $mimetype the document mime type
     * @param bool   $inline   True to send the file inline to the browser. The document viewer is used if available.
     *                         False to send to the browser and force a file download with the name given.
     */
    public static function buildHeaders(string $name, string $mimetype, bool $inline): array
    {
        $encoded = Utils::ascii($name);

        if ($inline) {
            $type = $mimetype;
            $disposition = HeaderUtils::DISPOSITION_INLINE;
        } else {
            $type = 'application/x-download';
            $disposition = HeaderUtils::DISPOSITION_ATTACHMENT;
        }

        return [
            'Pragma' => 'public',
            'Content-Type' => $type,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name, $encoded),
        ];
    }
}
