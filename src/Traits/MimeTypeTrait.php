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

namespace App\Traits;

use App\Util\Utils;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Trait to create file response headers.
 */
trait MimeTypeTrait
{
    /**
     * Build response header for an attachment.
     *
     * @param string $name   the document name
     * @param bool   $inline true to send the file inline to the browser. The document viewer is used if available,
     *                       false to send to the browser and force a file download with the name given.
     */
    public function buildHeaders(string $name, bool $inline): array
    {
        $encoded = Utils::ascii($name);
        if ($inline) {
            $type = $this->getMimeType();
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

    /**
     * Gets the mime type.
     */
    abstract public function getMimeType(): string;
}
