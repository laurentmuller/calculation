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
 * Contains information about an image data.
 */
class ImageData
{
    /** @var array{0: int, 1: int}|false|null */
    private array|false|null $size = null;

    /**
     * @param string  $data     the image data
     * @param ?string $mimeType the image mime type
     * @param ?string $fileType the image file type
     * @param ?string $fileName the image file name
     */
    public function __construct(
        private readonly string $data,
        private ?string $mimeType = null,
        private ?string $fileType = null,
        private ?string $fileName = null
    ) {
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
     * Gets the image size.
     *
     * @return array{0: int, 1: int}|false the image width and height, if applicable; false otherwise
     */
    public function getSize(): array|false
    {
        if (null === $this->size) {
            $size = \getimagesizefromstring($this->data);
            $this->size = \is_array($size) ? [$size[0], $size[1]] : false;
        }

        return $this->size;
    }

    /**
     * Creates a new instance.
     *
     * @param string  $data     the image data
     * @param ?string $mimeType the image mime type
     * @param ?string $fileType the image file type
     * @param ?string $fileName the image file name
     */
    public static function instance(
        string $data,
        ?string $mimeType = null,
        ?string $fileType = null,
        ?string $fileName = null
    ): self {
        return new self($data, $mimeType, $fileType, $fileName);
    }
}
