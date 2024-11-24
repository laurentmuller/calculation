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

use App\Utils\StringUtils;

/**
 * Service to get Font Awesome icons.
 */
class FontAwesomeIconService
{
    private const EXCLUDED = [
        // fixed width
        'fa-fw',
        // sizing
        'fa-2xs',
        'fa-xs',
        'fa-sm',
        'fa-lg',
        'fa-xl',
        'fa-2xl',
        'fa-1x',
        'fa-2x',
        'fa-3x',
        'fa-4x',
        'fa-5x',
        'fa-6x',
        'fa-7x',
        'fa-8x',
        'fa-9x',
        'fa-10x',
        // rotate
        'fa-rotate-90',
        'fa-rotate-180',
        'fa-rotate-270',
        'fa-flip-horizontal',
        'fa-flip-vertical',
        'fa-flip-both',
        'fa-rotate-by',
        // animating
        'fa-beat',
        'fa-beat-fade',
        'fa-bounce',
        'fa-flip',
        'fa-shake',
        'fa-spin',
        'fa-spin-pulse',
        'fa-spin-reverse',
        // bordered and pulled
        'fa-border',
        'fa-pull-right',
        'fa-pull-left',
        // stacking
        'fa-stack',
        'fa-stack-1x',
        'fa-stack-2x',
        'fa-inverse',
        // transforms
        'fa-seedling',
    ];

    private const FOLDERS = [
        'brands',
        'regular',
        'solid',
    ];

    /**
     * Gets the relative path for the given icon class.
     *
     * @param string $icon the icon class to convert. An icon like 'fa-solid fa-eye' will be
     *                     converted to 'solid/eye.svg'.
     *
     * @return ?string the relative path, if applicable; null otherwise
     */
    public function getPath(string $icon): ?string
    {
        $icon = $this->cleanIcon($icon);
        if ('' === $icon) {
            return null;
        }
        $parts = \array_unique(\explode(' ', $icon));
        if (2 !== \count($parts)) {
            return null;
        }

        return match (true) {
            $this->isFolder($parts[0]) => \sprintf(
                '%s/%s%s',
                $parts[0],
                $parts[1],
                FontAwesomeImageService::SVG_EXTENSION
            ),
            $this->isFolder($parts[1]) => \sprintf(
                '%s/%s%s',
                $parts[1],
                $parts[0],
                FontAwesomeImageService::SVG_EXTENSION
            ),
            default => null,
        };
    }

    private function cleanIcon(string $icon): string
    {
        $icon = \str_replace(self::EXCLUDED, '', \strtolower($icon));
        $icon = \str_replace('fa-', '', $icon);

        return \trim(StringUtils::pregReplace('/\s+/', ' ', $icon));
    }

    private function isFolder(string $name): bool
    {
        return \in_array($name, self::FOLDERS, true);
    }
}
