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

use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\PdfRectangle;

/**
 * An abstract specialized cell containing an image and an optional text.
 */
abstract class AbstractPdfImageCell extends PdfCell
{
    /**
     * Draw this image and text.
     *
     * @param PdfDocument       $parent    the parent to output image and text to
     * @param PdfRectangle      $bounds    the cell bounds
     * @param ?PdfTextAlignment $alignment the image and text alignment
     * @param PdfMove           $move      indicates where the current position should go after the call
     */
    public function drawImage(
        PdfDocument $parent,
        PdfRectangle $bounds,
        ?PdfTextAlignment $alignment = null,
        PdfMove $move = PdfMove::RIGHT
    ): void {
        // style
        $this->getStyle()?->apply($parent);

        // convert size
        $imageWidth = $parent->pixels2UserUnit($this->getWidth());
        $imageHeight = $parent->pixels2UserUnit($this->getHeight());

        // compute text
        $text = $this->getText() ?? '';
        $cellMargin = $parent->getCellMargin();
        $maxWidth = $bounds->width - $imageWidth - 3.0 * $cellMargin;
        $textWidth = $parent->getStringWidth($text);
        while ('' !== $text && $textWidth > $maxWidth) {
            $text = \substr($text, 0, -1);
            $textWidth = $parent->getStringWidth($text);
        }

        // total width
        $totalWidth = $textWidth > 0 ? $imageWidth + $cellMargin + $textWidth : $imageWidth;

        // set position
        $alignment ??= $this->getAlignment() ?? PdfTextAlignment::LEFT;
        $x = match ($alignment) {
            PdfTextAlignment::RIGHT => $bounds->right() - $totalWidth - $cellMargin,
            PdfTextAlignment::CENTER,
            PdfTextAlignment::JUSTIFIED => $bounds->x + ($bounds->width - $totalWidth) / 2.0,
            default => $bounds->x + $cellMargin,
        };
        $y = $bounds->y;

        // image
        $parent->image(
            $this->getPath(),
            $x,
            $y + $cellMargin,
            $imageWidth,
            $imageHeight,
            $this->getType(),
            $this->getLink()
        );

        // text
        if ('' !== $text) {
            $parent->setXY($x + $imageWidth, $y);
            $parent->cell(
                width: $textWidth,
                text: $text,
                link: $this->getLink()
            );
        }

        // move
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

    /**
     *  Gets the image height in pixels.
     * /
     */
    abstract public function getHeight(): int;

    /**
     * Gets the image path.
     */
    abstract public function getPath(): string;

    /**
     * Gets the image type.
     */
    public function getType(): string
    {
        return '';
    }

    /**
     * Gets the image width in pixels.
     */
    abstract public function getWidth(): int;
}
