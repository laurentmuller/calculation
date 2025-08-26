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

namespace App\Service;

use Twig\Attribute\AsTwigFunction;

/**
 * Service to get Avatar images from https://robohash.org/.
 */
class AvatarService
{
    /**
     * Gets the avatar URL image for the given name.
     *
     * @param string $name       the non-empty name to use
     * @param int    $size       the image size (only used if the value is greater than 0)
     * @param int    $set        the image set (only used if the value is between 1 and 5 inclusive)
     * @param int    $background the background set (only used if the value is between 1 and 2 inclusive)
     *
     * @phpstan-param non-empty-string $name
     * @phpstan-param int<0,5> $set
     * @phpstan-param int<0,2> $background
     */
    #[AsTwigFunction(name: 'avatar', isSafe: ['html'])]
    public function getURL(string $name, int $size = 32, int $set = 0, int $background = 0): string
    {
        $query = [];
        if ($size > 0) {
            $query['size'] = \sprintf('%dx%d', $size, $size);
        }
        if (\in_array($set, \range(1, 5), true)) {
            $query['set'] = \sprintf('set%d', $set);
        }
        if (\in_array($background, \range(1, 2), true)) {
            $query['bgset'] = \sprintf('bg%d', $background);
        }
        $url = \sprintf('https://robohash.org/%s', \urlencode($name));
        if ([] !== $query) {
            $url .= '?' . \http_build_query($query);
        }

        return $url;
    }
}
