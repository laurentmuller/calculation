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
use fpdf\Enums\PdfTextAlignment;

/**
 * A specialized cell containing a FontAwesome image and an optional text.
 */
class PdfFontAwesomeCell extends AbstractPdfImageCell
{
    /**
     * The image data.
     */
    private ImageData $imageData;

    /**
     * The image size.
     *
     * @var array{0: int, 1: int}
     */
    private array $size;

    /**
     * @param FontAwesomeImage  $image     the FontAwesome image to output
     * @param ?string           $text      the cell text
     * @param int               $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int|null   $link      the optional cell link.
     *                                     A URL or identifier returned by the <code>addLink()</code> function.
     *
     * @psalm-param positive-int $cols
     */
    public function __construct(
        FontAwesomeImage $image,
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ) {
        $this->size = $image->resize(12);
        $this->imageData = ImageData::instance($image->getContent());
        parent::__construct($text, $cols, $style, $alignment, $link);
    }

    public function getHeight(): int
    {
        return $this->size[1];
    }

    public function getPath(): string
    {
        return $this->imageData->getFileName();
    }

    public function getType(): string
    {
        return $this->imageData->getFileType();
    }

    public function getWidth(): int
    {
        return $this->size[0];
    }
}
