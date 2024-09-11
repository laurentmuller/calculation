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
    public function __construct(
        private string $content,
        private int $width,
        private int $height,
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
        return $this->height;
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
        return $this->width;
    }

    /**
     * Gets a scaled width and height to the desired size.
     *
     * @param int $size the desired size.
     *                  If this width is greater than this height, then the width is set to the given size and
     *                  the height is calculated.
     *                  If this height is greater than this width, then the height is set to the given size and
     *                  the width is calculated.
     *
     * @return array{0: int, 1: int} an array where the first element is the scaled width or the desired size and
     *                               the second element is the scaled height or the desired size
     */
    public function resize(int $size): array
    {
        if ($this->width === $this->height) {
            return [$size, $size];
        }

        if ($this->width > $this->height) {
            return [$size, $this->round($size * $this->height, $this->width)];
        }

        return [$this->round($size * $this->width, $this->height), $size];
    }

    private function round(float $dividend, float $divisor): int
    {
        return (int) \round($dividend / $divisor);
    }
}
