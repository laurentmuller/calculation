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

use fpdf\PdfTextAlignment;

/**
 * Define a cell in a table.
 */
class PdfCell
{
    /**
     * @param ?string           $text      the cell text
     * @param int               $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int|null   $link      the cell link. A URL or identifier returned by AddLink().
     *
     * @psalm-param positive-int $cols
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
     * Gets the cell alignment.
     */
    public function getAlignment(): ?PdfTextAlignment
    {
        return $this->alignment;
    }

    /**
     * Gets the cell columns span.
     *
     * @psalm-return positive-int
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
     */
    public function isLink(): bool
    {
        return PdfDocument::isLink($this->link);
    }
}
