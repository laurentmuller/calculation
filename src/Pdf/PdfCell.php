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
 *
 * @author Laurent Muller
 */
class PdfCell
{
    /**
     * The cell alignment.
     */
    protected ?PdfTextAlignment $alignment = null;

    /**
     * The cell columns span.
     */
    protected int $cols = 1;

    /**
     * The cell style.
     */
    protected ?PdfStyle $style = null;

    /**
     * The cell text.
     */
    protected ?string $text = null;

    /**
     * Constructor.
     *
     * @param string|null           $text      the cell text
     * @param int                   $cols      the cell columns span
     * @param PdfStyle|null         $style     the cell style
     * @param PdfTextAlignment|null $alignment the cell alignment
     */
    public function __construct(?string $text = null, int $cols = 1, ?PdfStyle $style = null, ?PdfTextAlignment $alignment = null)
    {
        $this->setText($text)
            ->setCols($cols)
            ->setStyle($style)
            ->setAlignment($alignment);
    }

    public function __clone()
    {
        if (null !== $this->style) {
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
