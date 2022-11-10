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

namespace App\Interfaces;

/**
 * Class implementing this interface provides function for download document.
 */
interface MimeTypeInterface
{
    /**
     * Gets the attachment mime type.
     */
    public function getAttachmentMimeType(): string;

    /**
     * Gets the default file extension (without the dot separator).
     */
    public function getFileExtension(): string;

    /**
     * Gets the inline mime type.
     */
    public function getInlineMimeType(): string;
}
