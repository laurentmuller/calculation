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

use App\Model\ImageSize;

/**
 * Trait ot get image size.
 */
trait ImageSizeTrait
{
    /**
     * Gets the image size for the given file name.
     *
     * @throws \InvalidArgumentException if the file does not exist or is not an image
     */
    public function getImageSize(string $filename): ImageSize
    {
        if (!\file_exists($filename)) {
            throw new \InvalidArgumentException(\sprintf('The file "%s" does not exist.', $filename));
        }
        $size = \getimagesize($filename);
        if (false === $size) {
            throw new \InvalidArgumentException(\sprintf('Unable to get image size for "%s".', $filename));
        }

        return ImageSize::instance($size[0], $size[1]);
    }
}
