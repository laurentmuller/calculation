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
use App\Pdf\Enums\PdfCellImagePosition;
use App\Pdf\Interfaces\PdfCellOutputInterface;
use App\Utils\StringUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\PdfException;
use fpdf\PdfRectangle;

/**
 * An abstract specialized cell containing an image and an optional text.
 */
abstract class AbstractPdfImageCell extends PdfCell implements PdfCellOutputInterface
{
    /**
     * @param ?string               $text          the cell text
     * @param positive-int          $cols          the cell columns span
     * @param ?PdfStyle             $style         the cell style
     * @param ?PdfTextAlignment     $alignment     the cell alignment
     * @param string|int|null       $link          the optional cell link
     * @param ?PdfCellImagePosition $imagePosition the image position
     *
     * @throws PdfException if the given image path does not exist
     */
    public function __construct(
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        int|string|null $link = null,
        private readonly ?PdfCellImagePosition $imagePosition = null
    ) {
        parent::__construct($text, $cols, $style, $alignment, $link);
    }

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
     * Gets the image position.
     */
    public function getImagePosition(): ?PdfCellImagePosition
    {
        return $this->imagePosition;
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
        $this->getStyle()?->updateDocument($parent);

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
        $totalWidth = 2.0 * $cellMargin + $imageWidth + ('' !== $text ? $textWidth + $cellMargin : 0.0);

        // start position
        $alignment ??= $this->getAlignment() ?? PdfTextAlignment::LEFT;
        $x = match ($alignment) {
            PdfTextAlignment::RIGHT => $bounds->right() - $totalWidth,
            PdfTextAlignment::CENTER,
            PdfTextAlignment::JUSTIFIED => $bounds->x + ($bounds->width - $totalWidth) / 2.0,
            default => $bounds->x,
        };
        $y = $bounds->y;

        // image and text positions
        $imageX = $x + $cellMargin;
        $imageY = $y + \max($cellMargin, (PdfDocument::LINE_HEIGHT - $imageHeight) / 2.0);
        $position = $this->getImagePosition() ?? PdfCellImagePosition::DEFAULT;
        if (PdfCellImagePosition::LEFT === $position) {
            $textX = $imageX + $imageWidth;
        } else {
            $textX = $x;
            if ('' !== $text) {
                $imageX += $textWidth + $cellMargin;
            }
        }

        // output
        $parent->image(
            file: $this->getPath(),
            x: $imageX,
            y: $imageY,
            width: $imageWidth,
            height: $imageHeight,
            type: $this->getType(),
            link: $this->getLink()
        );
        if ('' !== $text) {
            $parent->setXY($textX, $y)
                ->cell(width: $textWidth, text: $text, link: $this->getLink());
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
