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

use App\Utils\StringUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\PdfRectangle;

/**
 * Define a cell in a table.
 */
class PdfCell
{
    /**
     * @param ?string           $text      the cell text
     * @param positive-int      $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int|null   $link      the optional cell link.
     *                                     A URL or identifier returned by the <code>addLink()</code> function.
     */
    public function __construct(
        private readonly ?string $text = null,
        private readonly int $cols = 1,
        private ?PdfStyle $style = null,
        private readonly ?PdfTextAlignment $alignment = null,
        private readonly string|int|null $link = null
    ) {
    }

    public function __clone()
    {
        if ($this->style instanceof PdfStyle) {
            $this->style = clone $this->style;
        }
    }

    /**
     * Gets the required width; include cell margins.
     *
     * @param PdfDocument $parent the parent to apply this style and compute this text
     */
    public function computeWidth(PdfDocument $parent): float
    {
        $width = 2.0 * $parent->getCellMargin();
        if (StringUtils::isString($this->text)) {
            $this->getStyle()?->apply($parent);
            $width += $parent->getStringWidth($this->text);
        }

        return $width;
    }

    /**
     * Gets the cell alignment.
     */
    public function getAlignment(): ?PdfTextAlignment
    {
        return $this->alignment;
    }

    /**
     * Gets the cell columns span.
     *
     * @return positive-int
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Gets the cell link.
     */
    public function getLink(): string|int|null
    {
        return $this->link;
    }

    /**
     * Gets the cell style.
     */
    public function getStyle(): ?PdfStyle
    {
        return $this->style;
    }

    /**
     * Gets the text.
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Return a value indicating if this link is valid.
     *
     * @phpstan-assert-if-true (non-empty-string|positive-int) $this->link
     */
    public function hasLink(): bool
    {
        return PdfDocument::isLink($this->link);
    }

    /**
     * Apply this style, if any, and output this text.
     *
     * @param PdfDocument       $parent    the parent to output text to
     * @param PdfRectangle      $bounds    the cell bounds
     * @param ?PdfTextAlignment $alignment the text alignment
     * @param PdfMove           $move      indicates where the current position should go after the call
     *
     * @see PdfDocument::cell()
     */
    public function output(
        PdfDocument $parent,
        PdfRectangle $bounds,
        ?PdfTextAlignment $alignment = null,
        PdfMove $move = PdfMove::RIGHT
    ): void {
        $this->style?->apply($parent);
        $parent->setXY($bounds->x, $bounds->y);
        $alignment ??= $this->alignment ?? PdfTextAlignment::LEFT;
        $parent->cell(
            width: $bounds->width,
            height: $bounds->height,
            text: $this->text ?? '',
            move: $move,
            align: $alignment,
            link: $this->link
        );
    }
}
