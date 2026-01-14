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
     * @param ImageSize $imageSize  the image size in pixels
     * @param int       $resolution the resolution in DPI (dot per each)
     */
    public function __construct(
        private string $content,
        private ImageSize $imageSize,
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
     * Gets the image height in pixels.
     */
    public function getHeight(): int
    {
        return $this->imageSize->height;
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
     * Gets the image width in pixels.
     */
    public function getWidth(): int
    {
        return $this->imageSize->width;
    }

    /**
     * Gets a scaled image size to the desired size.
     */
    public function resize(int $size): ImageSize
    {
        return $this->imageSize->resize($size);
    }
}
