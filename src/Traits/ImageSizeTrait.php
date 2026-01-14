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
     * @return ImageSize the image size, if success; an empty size (0, 0) if fail
     */
    public function getImageSize(string $filename): ImageSize
    {
        if ('' === $filename) {
            return ImageSize::instance(0, 0);
        }

        $size = \getimagesize($filename);
        if (false === $size) {
            return ImageSize::instance(0, 0);
        }

        return ImageSize::instance($size[0], $size[1]);
    }
}
