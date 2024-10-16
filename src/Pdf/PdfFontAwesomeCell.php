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
use App\Pdf\Traits\PdfImageTypeTrait;
use fpdf\Enums\PdfTextAlignment;

/**
 * A specialized cell containing a FontAwesome image and an optional text.
 */
class PdfFontAwesomeCell extends AbstractPdfImageCell
{
    use PdfImageTypeTrait;

    /**
     * The image height.
     */
    private int $height;

    /**
     * The image path.
     */
    private string $path;

    /**
     * The image type.
     */
    private string $type;

    /**
     * The image width.
     */
    private int $width;

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
        private readonly FontAwesomeImage $image,
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ) {
        parent::__construct($text, $cols, $style, $alignment, $link);

        $data = $this->image->getContent();
        $mimeType = $this->getImageMimeType($data);
        $this->type = $this->getImageFileType($mimeType);
        $this->path = $this->getImageFileName($mimeType, $data);

        $size = $this->image->resize(12);
        $this->width = $size[0];
        $this->height = $size[1];
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getWidth(): int
    {
        return $this->width;
    }
}
