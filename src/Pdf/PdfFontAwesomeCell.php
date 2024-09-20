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
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\PdfRectangle;

/**
 * PDF cell to output a FontAwesome image and an optional text.
 */
class PdfFontAwesomeCell extends PdfCell
{
    use PdfImageTypeTrait;

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
    }

    /**
     * Draw this FontAwesome image and text to given cell bounds.
     */
    public function drawImage(
        PdfDocument $parent,
        PdfRectangle $bounds,
        ?PdfTextAlignment $alignment = null,
        PdfMove $move = PdfMove::RIGHT
    ): void {
        $size = $this->image->resize(12.0);
        $width = $parent->pixels2UserUnit($size[0]);
        $height = $parent->pixels2UserUnit($size[1]);
        $offset = (PdfDocument::LINE_HEIGHT - $height) / 2.0;

        $text = $this->getText() ?? '';
        $cellMargin = $parent->getCellMargin();
        $maxWidth = $bounds->width - $width - 3.0 * $cellMargin;
        $textWidth = $parent->getStringWidth($text);
        while ('' !== $text && $textWidth > $maxWidth) {
            $text = \substr($text, 0, \strlen($text) - 1);
            $textWidth = $parent->getStringWidth($text);
        }

        $totalWidth = $width + $textWidth;
        if ($textWidth > 0) {
            $totalWidth += $cellMargin;
        }

        $alignment ??= $this->getAlignment() ?? PdfTextAlignment::LEFT;
        $x = match ($alignment) {
            PdfTextAlignment::RIGHT => $bounds->right() - $totalWidth - $cellMargin,
            PdfTextAlignment::CENTER,
            PdfTextAlignment::JUSTIFIED => $bounds->x + ($bounds->width - $totalWidth) / 2.0,
            default => $bounds->x + $cellMargin,
        };
        $y = $bounds->y;

        $data = $this->image->getContent();
        $mimeType = $this->getImageMimeType($data);
        $fileType = $this->getImageFileType($mimeType);
        $fileName = $this->getImageFileName($mimeType, $data);
        $parent->image(
            file: $fileName,
            x: $x,
            y: $y + $offset,
            width: $width,
            height: $height,
            type: $fileType,
            link: $this->getLink()
        );
        if ('' !== $text) {
            $parent->setXY($x + $width, $y);
            $parent->cell(
                width: $textWidth,
                text: $text,
                link: $this->getLink()
            );
        }
        switch ($move) {
            case PdfMove::RIGHT:
                $parent->setXY($bounds->right(), $bounds->y);
                break;
            case PdfMove::NEW_LINE:
                $parent->setXY($parent->getLeftMargin(), $bounds->bottom());
                break;
            case PdfMove::BELOW:
                $parent->setXY($bounds->x, $bounds->bottom());
                break;
        }
    }
}
