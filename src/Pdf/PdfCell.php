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

use App\Pdf\Enums\PdfTextAlignment;

/**
 * Define a cell in a table.
 */
class PdfCell
{
    /**
     * Constructor.
     *
     * @param ?string           $text      the cell text
     * @param int               $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int        $link      the cell link. A URL or identifier returned by AddLink().
     */
    public function __construct(private ?string $text = null, private int $cols = 1, private ?PdfStyle $style = null, private ?PdfTextAlignment $alignment = null, private string|int $link = '')
    {
        $this->setText($text)
            ->setCols($cols)
            ->setStyle($style)
            ->setAlignment($alignment)
            ->setLink($link);
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
     * Gets the columns span.
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Gets the link.
     */
    public function getLink(): string|int
    {
        return $this->link;
    }

    /**
     * Gets the style.
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
     * Return a value indicating if this link is not an empty string.
     */
    public function isLink(): bool
    {
        return '' !== $this->link;
    }

    /**
     * Sets the cell alignment.
     */
    public function setAlignment(?PdfTextAlignment $alignment): self
    {
        $this->alignment = $alignment;

        return $this;
    }

    /**
     * Sets the columns span.
     */
    public function setCols(int $cols): self
    {
        $this->cols = \max(1, $cols);

        return $this;
    }

    /**
     * Sets the link.
     */
    public function setLink(string|int $link = ''): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Sets the style.
     */
    public function setStyle(?PdfStyle $style): self
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Sets the text.
     */
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }
}
