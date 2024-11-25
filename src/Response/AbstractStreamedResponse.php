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
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;

/**
 * This abstract class extends streamed response with the mime type interface.
 */
abstract class AbstractStreamedResponse extends StreamedResponse implements MimeTypeInterface
{
    private ?string $mimeType = null;

    public function __construct(?callable $callback = null, bool $inline = true, string $name = '')
    {
        $headers = $this->buildHeaders($name, $inline);
        parent::__construct(callback: $callback, headers: $headers);
    }

    public function getAttachmentMimeType(): string
    {
        return 'application/x-download';
    }

    public function getInlineMimeType(): string
    {
        if (null === $this->mimeType) {
            $ext = $this->getFileExtension();
            $types = MimeTypes::getDefault();
            $this->mimeType = $types->getMimeTypes($ext)[0];
        }

        return $this->mimeType;
    }

    /**
     * Build response header for an attachment.
     *
     * @param string $name   the document name
     * @param bool   $inline <code>true</code> to send the file inline to the browser. The document viewer is used
     *                       if available, <code>false</code> to send to the browser and force a file download with
     *                       the name given.
     */
    private function buildHeaders(string $name, bool $inline): array
    {
        $name = $this->validate($name);
        $encoded = StringUtils::ascii($name);
        $type = $inline ? $this->getInlineMimeType() : $this->getAttachmentMimeType();
        $disposition = $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT;

        return [
            'Pragma' => 'public',
            'Content-Type' => $type,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name, $encoded),
        ];
    }

    /**
     * Validate the given document name.
     */
    private function validate(string $name): string
    {
        $name = '' === $name ? 'document' : \basename($name);
        $ext = '.' . $this->getFileExtension();
        if (!\str_ends_with($name, $ext)) {
            return $name . $ext;
        }

        return $name;
    }
}
