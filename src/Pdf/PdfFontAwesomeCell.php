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

namespace App\Pdf;

use App\Model\FontAwesomeImage;
use App\Model\ImageData;
use App\Model\ImageSize;
use fpdf\Enums\PdfTextAlignment;

/**
 * A specialized cell containing a FontAwesome image and an optional text.
 */
class PdfFontAwesomeCell extends AbstractPdfImageCell
{
    /**
     * The image data.
     */
    private readonly ImageData $imageData;

    /**
     * The image size.
     */
    private readonly ImageSize $size;

    /**
     * @param FontAwesomeImage  $image     the FontAwesome image to output
     * @param ?string           $text      the cell text
     * @param int               $size      the desired image size
     * @param positive-int      $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int|null   $link      the optional cell link
     */
    public function __construct(
        FontAwesomeImage $image,
        ?string $text = null,
        int $size = 11,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ) {
        parent::__construct($text, $cols, $style, $alignment, $link);
        $this->size = $image->resize($size);
        $this->imageData = ImageData::instance(
            data: $image->getContent(),
            mimeType: $image->getMimeType()
        );
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->imageData->getFileName();
    }

    #[\Override]
    public function getSize(): ImageSize
    {
        return $this->size;
    }

    #[\Override]
    public function getType(): string
    {
        return $this->imageData->getFileType();
    }
}
