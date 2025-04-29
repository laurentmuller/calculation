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

/**
 * Service to get Font Awesome icons.
 */
class FontAwesomeIconService
{
    // extracted from https://docs.fontawesome.com/web/style/style-cheatsheet
    private const EXCLUDED = [
        // General
        'fa-inverse', // Inverts the color of an icon to white
        // Sizing Icons
        'fa-1x',  // Changes an icon’s font-size to 1.0em
        'fa-2x',  // Changes an icon’s font-size to 2.0em
        'fa-3x',  // Changes an icon’s font-size to 3.0em
        'fa-4x',  // Changes an icon’s font-size to 4.0em
        'fa-5x',  // Changes an icon’s font-size to 5.0em
        'fa-6x',  // Changes an icon’s font-size to 6.0em
        'fa-7x',  // Changes an icon’s font-size to 7.0em
        'fa-8x',  // Changes an icon’s font-size to 8.0em
        'fa-9x',  // Changes an icon’s font-size to 9.0em
        'fa-10x', // Changes an icon’s font-size to 10.0em
        'fa-2xs', // Changes an icon’s font-size to 0.625em (~10px) and vertically aligns
        'fa-xs',  // Changes an icon’s font-size to 0.75em (~12px) and vertically aligns
        'fa-sm',  // Changes an icon’s font-size to 0.875em (~14px) and vertically aligns
        'fa-lg',  // Changes an icon’s font-size to 1.25em (~120px) and vertically aligns
        'fa-xl',  // Changes an icon’s font-size to 1.5em (~24px) and vertically aligns
        'fa-2xl', // Changes an icon’s font-size to 2.0em (~32px) and vertically aligns
        // Fixed-Width Icons
        'fa-fw', // Sets an icon to display at a fixed width for easy vertical alignment
        // Icons in a List
        'fa-ul', // Used on order or unordered list elements to style icons as list bullets
        'fa-li', // Used on individual list item elements to style icons as list bullets
        // Rotating Icons
        'fa-rotate-90', //  Rotates an icon 90°
        'fa-rotate-180', // Rotates an icon 180°
        'fa-rotate-270', // Rotates an icon 270°
        'fa-flip-horizontal', // Mirrors an icon horizontally
        'fa-flip-vertical', // Mirrors an icon vertically
        'fa-flip-both', // Mirrors an icon both vertically and horizontally
        'fa-rotate-by', // Rotates an icon by a specific degree
        // Animating Icons
        'fa-spin', // Makes an icon spin 360° clock-wise
        'fa-spin-pulse', // Makes an icon spin 360° clock-wise in 8 incremental steps
        'fa-spin-reverse', // When used in conjunction with fa-spin or fa-spin-pulse, makes an icon spin counter-clockwise
        'fa-beat', // Makes an icon scale up and down
        'fa-fade', // Makes an icon visually fade in and out using opacity
        'fa-flip', // Makes an icon rotate about the Y axis
        // Bordered Icons
        'fa-border', // Creates a border with border-radius and padding applied around an icon
        // Pulled Icons
        'fa-pull-left', // Pulls an icon by floating it left and applying a margin-right
        'fa-pull-right', // Pulls an icon by floating it right and applying a margin-left
        // Stacking Icons
        'fa-stack', // Used on a parent HTML element of the two icons to be stacked
        'fa-stack-1x', // Used on the icon, which should be displayed at base size when stacked
        'fa-stack-2x', // Used on the icon, which should be displayed larger when stacked
        // Duotone Icons
        'fa-swap-opacity', // Swap the default opacity of each layer in a duotone icon
        // Accessibility
        'fa-sr-only', // Visually hides an element containing a text-equivalent
        'fa-sr-only-focusable', // Used alongside fa-sr-only to show the element again when it is focused
    ];

    private const FOLDERS = [
        'brands',
        'regular',
        'solid',
    ];

    /**
     * Gets the relative path for the given icon class.
     *
     * @param string $icon the icon class to convert.
     *                     An icon like 'fa-solid fa-eye' will be converted to 'solid/eye.svg'.
     *
     * @return ?string the relative path, if applicable; null otherwise
     */
    public function getPath(string $icon): ?string
    {
        $parts = $this->splitIcon($icon);
        if (2 !== \count($parts)) {
            return null;
        }

        if ($this->isFolder($parts[0])) {
            return \sprintf(
                '%s/%s%s',
                $parts[0],
                $parts[1],
                FontAwesomeImageService::SVG_EXTENSION
            );
        }

        if ($this->isFolder($parts[1])) {
            return \sprintf(
                '%s/%s%s',
                $parts[1],
                $parts[0],
                FontAwesomeImageService::SVG_EXTENSION
            );
        }

        return null;
    }

    private function isFolder(string $name): bool
    {
        return \in_array($name, self::FOLDERS, true);
    }

    /**
     * @return string[]
     */
    private function splitIcon(string $icon): array
    {
        $values = \array_filter(\explode(' ', \strtolower($icon)));
        if (\count($values) < 2) {
            return [];
        }

        $values = \array_diff($values, self::EXCLUDED);
        if (2 !== \count($values)) {
            return [];
        }

        return \array_map(fn (string $value): string => \str_replace('fa-', '', $value), \array_values($values));
    }
}
