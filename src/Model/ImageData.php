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

namespace App\Model;

/**
 * Contains information about a image data.
 */
class ImageData
{
    private ?string $fileName = null;
    private ?string $fileType = null;
    private ?string $mimeType = null;

    /**
     * @param string $data the image data
     */
    public function __construct(private readonly string $data)
    {
    }

    /**
     * Gets the image data.
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Get the image file name.
     */
    public function getFileName(): string
    {
        if (null === $this->fileName) {
            $mimeType = $this->getMimeType();
            $encoded = \base64_encode($this->getData());
            $this->fileName = \sprintf('data://%s;base64,%s', $mimeType, $encoded);
        }

        return $this->fileName;
    }

    /**
     * Gets the image file type.
     */
    public function getFileType(): string
    {
        if (null === $this->fileType) {
            $mimeType = $this->getMimeType();
            $this->fileType = \substr((string) \strrchr($mimeType, '/'), 1);
        }

        return $this->fileType;
    }

    /**
     * Gets the image mime type.
     */
    public function getMimeType(): string
    {
        if (null === $this->mimeType) {
            $info = new \finfo(\FILEINFO_MIME_TYPE);
            $this->mimeType = (string) $info->buffer($this->data);
        }

        return $this->mimeType;
    }

    /**
     * Creates a new instance for the given data.
     *
     * @param string $data the image data
     */
    public static function instance(string $data): self
    {
        return new self($data);
    }
}
