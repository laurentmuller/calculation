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
 * Contains an image size (width and height).
 */
class ImageSize
{
    /**
     * @param int $width  the image width
     * @param int $height the image height
     */
    public function __construct(
        public int $width,
        public int $height,
    ) {
    }

    /**
     * Create a new instance.
     */
    public static function instance(int $width, int $height): self
    {
        return new self($width, $height);
    }

    public function isEmpty(): bool
    {
        return 0 === $this->width || 0 === $this->height;
    }

    /**
     * Gets a scaled width and height to the desired size.
     *
     * @param int $size the desired size. Values are computed as follows:
     *                  <li>If this width is greater than this height, then width is set to the desired size and
     *                  height is calculated.</li>
     *                  <li>If this height is greater than this width, then height is set to the desired size and
     *                  width is calculated.</li>
     *                  <li>If both values are equal, then return the desired size for both values.</li>
     *                  </ul>
     */
    public function resize(int $size): self
    {
        if ($this->width === $this->height) {
            return self::instance($size, $size);
        }

        if ($this->width > $this->height) {
            return self::instance($size, (int) \ceil($size * $this->height / $this->width));
        }

        return self::instance((int) \ceil($size * $this->width / $this->height), $size);
    }
}
