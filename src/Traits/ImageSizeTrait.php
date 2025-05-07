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

/**
 * Trait ot get image size.
 */
trait ImageSizeTrait
{
    /**
     * Gets the image size for the given file name.
     *
     * @return array{0: int, 1: int} the image size with width and height, if success; an empty array ([0, 0]) if fail
     */
    public function getImageSize(string $filename): array
    {
        if ('' === $filename) {
            return [0, 0];
        }

        /** @phpstan-var int[]|false $size */
        $size = \getimagesize($filename);
        if (\is_array($size)) {
            return [$size[0], $size[1]];
        }

        return [0, 0];
    }
}
