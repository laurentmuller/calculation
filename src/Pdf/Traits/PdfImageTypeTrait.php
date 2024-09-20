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

namespace App\Pdf\Traits;

use fpdf\PdfException;

/**
 * Trait to get image information.
 */
trait PdfImageTypeTrait
{
    /**
     * Get the image file name.
     *
     * @param string $mimeType the image mime type
     * @param string $data     the image data
     */
    protected function getImageFileName(string $mimeType, string $data): string
    {
        return \sprintf('data://%s;base64,%s', $mimeType, \base64_encode($data));
    }

    /**
     * Gets the image file type.
     *
     * @param string $mimeType the image mime type
     */
    protected function getImageFileType(string $mimeType): string
    {
        return \substr((string) \strrchr($mimeType, '/'), 1);
    }

    /**
     * Gets the image mime type.
     *
     * @param string $data the image data
     */
    protected function getImageMimeType(string $data): string
    {
        $info = new \finfo(\FILEINFO_MIME_TYPE);
        $mime = $info->buffer($data);
        if (!\is_string($mime)) {
            throw PdfException::format('Empty or incorrect mime type: "%s".', (string) $mime);
        }

        return $mime;
    }
}
