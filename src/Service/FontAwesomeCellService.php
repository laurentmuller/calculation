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

use App\Model\FontAwesomeIcon;
use App\Model\FontAwesomeImage;
use App\Pdf\Enums\PdfCellImagePosition;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use fpdf\Enums\PdfTextAlignment;

/**
 * Service to get Font Awesome cell.
 */
readonly class FontAwesomeCellService
{
    public function __construct(private FontAwesomeImageService $imageService)
    {
    }

    /**
     * Gets a Font Awesome cell for the given icon class.
     *
     * @param FontAwesomeIcon       $icon          the icon to get image for
     * @param ?string               $color         the foreground color to apply or <code>null</code> for black color
     * @param int                   $size          the image size
     * @param ?string               $text          the cell text
     * @param positive-int          $cols          the cell columns span
     * @param ?PdfStyle             $style         the cell style
     * @param ?PdfTextAlignment     $alignment     the cell alignment
     * @param string|int|null       $link          the cell link
     * @param ?PdfCellImagePosition $imagePosition the image position
     *
     * @return ?PdfFontAwesomeCell the cell, if icon found, <code>null</code> otherwise
     */
    public function getCell(
        FontAwesomeIcon $icon,
        ?string $color = null,
        int $size = 11,
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null,
        ?PdfCellImagePosition $imagePosition = null
    ): ?PdfFontAwesomeCell {
        $image = $this->imageService->getImage($icon, $color);
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
            link: $link,
            imagePosition: $imagePosition
        );
    }
}
