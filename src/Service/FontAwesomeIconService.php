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
 * Service to convert Font Awesome icons to paths.
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
        // bordered & pulled
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
     * Convert the given icon class to a relative path.
     *
     * An icon like 'fa-solid fa-eye' is converted to 'solid/eye.svg'.
     *
     * @param string $icon the icon class to convert
     *
     * @return string|null the relative path, if applicable; null otherwise
     */
    public function getIconPath(string $icon): ?string
    {
        $icon = $this->cleanIcon($icon);
        $parts = \array_unique(\explode(' ', $icon));
        if (2 !== \count($parts)) {
            return null;
        }

        if ($this->isFolder($parts[0])) {
            return \sprintf('%s/%s%s', $parts[0], $parts[1], FontAwesomeService::SVG_EXTENSION);
        }
        if ($this->isFolder($parts[1])) {
            return \sprintf('%s/%s%s', $parts[1], $parts[0], FontAwesomeService::SVG_EXTENSION);
        }

        return null;
    }

    /**
     * Clean icon by removing unwanted strings.
     */
    private function cleanIcon(string $icon): string
    {
        // remove excluded classes
        $icon = \str_replace(self::EXCLUDED, '', \strtolower($icon));
        // replace 'fa-' prefix
        $icon = \str_replace('fa-', '', $icon);

        // suppress consecutive spaces
        return \trim((string) \preg_replace('/\s+/', ' ', $icon));
    }

    private function isFolder(string $name): bool
    {
        return \in_array($name, self::FOLDERS, true);
    }
}
