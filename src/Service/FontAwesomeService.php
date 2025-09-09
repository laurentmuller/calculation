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

use App\Model\FontAwesomeImage;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use fpdf\Enums\PdfTextAlignment;

/**
 * Service combine the FontAwesome image and icon services.
 */
readonly class FontAwesomeService
{
    public function __construct(
        private FontAwesomeImageService $imageService,
        private FontAwesomeIconService $iconService
    ) {
    }

    /**
     * Gets a Font Awesome cell for the given icon class.
     *
     * @param string            $icon      the icon class to convert
     * @param ?string           $color     the foreground color to apply or <code>null</code> for black color
     * @param int               $size      the image size
     * @param ?string           $text      the cell text
     * @param positive-int      $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int|null   $link      the cell link
     *
     * @return ?PdfFontAwesomeCell the cell, if icon found, <code>null</code> otherwise
     */
    public function getFontAwesomeCell(
        string $icon,
        ?string $color = null,
        int $size = 11,
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ): ?PdfFontAwesomeCell {
        $path = $this->getPath($icon);
        if (!\is_string($path)) {
            return null;
        }
        $image = $this->getImage($path, $color);
        if (!$image instanceof FontAwesomeImage) {
            return null;
        }

        return new PdfFontAwesomeCell(
            image: $image,
            text: $text,
            size: $size,
            cols: $cols,
            style: $style,
            alignment: $alignment,
            link: $link
        );
    }

    /**
     * Gets a Font Awesome image.
     *
     * @param string  $relativePath the relative file path to the SVG directory.
     *                              The SVG file extension (.svg) is added if not present.
     * @param ?string $color        the foreground color to apply or <code>null</code> for black color
     *
     * @return ?FontAwesomeImage the image, if found, <code>null</code> otherwise
     */
    public function getImage(string $relativePath, ?string $color = null): ?FontAwesomeImage
    {
        return $this->imageService->getImage($relativePath, $color);
    }

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
        return $this->iconService->getPath($icon);
    }
}
