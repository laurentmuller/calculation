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

use App\Model\ImageSize;
use App\Pdf\Interfaces\PdfCellOutputInterface;
use App\Utils\StringUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\PdfRectangle;

/**
 * An abstract specialized cell containing an image and an optional text.
 */
abstract class AbstractPdfImageCell extends PdfCell implements PdfCellOutputInterface
{
    /**
     * Override the default behavior by adding this image width.
     */
    #[\Override]
    public function computeWidth(PdfDocument $parent): float
    {
        $width = parent::computeWidth($parent);
        if (StringUtils::isString($this->getText())) {
            $width += $parent->getCellMargin();
        }

        return $width + $parent->pixels2UserUnit($this->getSize()->width);
    }

    /**
     * Gets the image path.
     */
    abstract public function getPath(): string;

    /**
     * Gets the image size.
     */
    abstract public function getSize(): ImageSize;

    /**
     * Gets the image type.
     */
    public function getType(): string
    {
        return '';
    }

    /**
     * Override the default behavior by output this image before the text.
     */
    #[\Override]
    public function output(
        PdfDocument $parent,
        PdfRectangle $bounds,
        ?PdfTextAlignment $alignment = null,
        PdfMove $move = PdfMove::RIGHT
    ): void {
        // style
        $this->getStyle()?->apply($parent);

        // convert size
        $size = $this->getSize();
        $imageWidth = $parent->pixels2UserUnit($size->width);
        $imageHeight = $parent->pixels2UserUnit($size->height);

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

        // vertical offset
        $offset = \max($cellMargin, (PdfDocument::LINE_HEIGHT - $imageHeight) / 2.0);

        // image
        $parent->image(
            $this->getPath(),
            $x,
            $y + $offset,
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
}
