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

namespace App\Enums;

use App\Service\FontAwesomeImageService;
use Symfony\Component\Filesystem\Path;

/**
 * The FontAwesome directory enumeration.
 */
enum FontAwesomePath: string
{
    case BRANDS = 'brands';
    case REGULAR = 'regular';
    case SOLID = 'solid';

    public function asHtml(string $icon, string $extra = ''): string
    {
        return \rtrim(\sprintf('fa-%s fa-%s %s', $this->value, $icon, $extra));
    }

    /**
     * Gets the absolute path of the given icon, include the SVG file extension.
     *
     * @param string $icon the icon name
     */
    public function getIconPath(string $icon): string
    {
        // ensure SVG extension
        if (!\str_ends_with($icon, FontAwesomeImageService::SVG_EXTENSION)) {
            $icon .= FontAwesomeImageService::SVG_EXTENSION;
        }

        return Path::join($this->getPath(), $icon);
    }

    /**
     * Gets the absolute path of icons.
     */
    public function getPath(): string
    {
        return Path::join(self::getRootPath(), $this->value);
    }

    /**
     * Gets the absolute root path of icons.
     */
    public static function getRootPath(): string
    {
        return Path::canonicalize(__DIR__ . '/../../resources/fontawesome');
    }
}
