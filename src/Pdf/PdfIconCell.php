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
use App\Pdf\Interfaces\PdfMemoryImageInterface;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\PdfRectangle;

/**
 * PDF cell to output a FontAwesome image and an optional text.
 */
class PdfIconCell extends PdfCell
{
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
     * Draw this FontAwesome image to given cell bounds.
     */
    public function drawImage(
        PdfDocument&PdfMemoryImageInterface $parent,
        PdfRectangle $bounds,
        PdfMove $move = PdfMove::RIGHT
    ): void {
        $size = $this->image->resize(12.0);
        $width = $parent->pixels2UserUnit($size[0]);
        $height = $parent->pixels2UserUnit($size[1]);
        $offset = (PdfDocument::LINE_HEIGHT - $height) / 2.0;

        $text = $this->getText() ?? '';
        $textWidth = $parent->getStringWidth($text);
        $cellMargin = $parent->getCellMargin();
        $totalWidth = $width + $cellMargin + $textWidth;

        $alignment = $this->getAlignment() ?? PdfTextAlignment::LEFT;
        $x = match ($alignment) {
            PdfTextAlignment::RIGHT => $bounds->right() - $cellMargin - $totalWidth,
            PdfTextAlignment::CENTER,
            PdfTextAlignment::JUSTIFIED => $bounds->x + ($bounds->width - $totalWidth) / 2.0,
            default => $bounds->x + $cellMargin,
        };
        $y = $bounds->y;

        $parent->imageMemory(
            data: $this->image->getContent(),
            x: $x,
            y: $y + $offset,
            width: $width,
            height: $height
        );
        $parent->setXY($x + $width, $y);
        $parent->cell(width: $textWidth, text: $text, move: $move);
    }
}
