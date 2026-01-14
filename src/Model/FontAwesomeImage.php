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
 * Contains a Font Awesome image.
 */
readonly class FontAwesomeImage
{
    /**
     * @param string    $content    the content, as 'png' format
     * @param ImageSize $size       the image size in pixels
     * @param int       $resolution the resolution in DPI (dot per each)
     */
    public function __construct(
        private string $content,
        private ImageSize $size,
        private int $resolution
    ) {
    }

    /**
     * Gets the image content, as 'png' format.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Gets the image mime type ('image/png').
     */
    public function getMimeType(): string
    {
        return 'image/png';
    }

    /**
     * Gets the image resolution in DPI (dot per each).
     */
    public function getResolution(): int
    {
        return $this->resolution;
    }

    /**
     * Gets the image size in pixels.
     */
    public function getSize(): ImageSize
    {
        return $this->size;
    }

    /**
     * Gets a scaled image size to the desired size.
     *
     * @see ImageSize::resize()
     */
    public function resize(int $size): ImageSize
    {
        return $this->size->resize($size);
    }
}
