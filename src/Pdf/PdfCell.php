<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf;

/**
 * Define a cell in a table.
 *
 * @author Laurent Muller
 */
class PdfCell
{
    use PdfAlignmentTrait;

    /**
     * The bottom border style.
     */
    protected ?PdfCellBorder $borderBottom = null;

    /**
     * The left border style.
     */
    protected ?PdfCellBorder $borderLeft = null;

    /**
     * The right border style.
     */
    protected ?PdfCellBorder $borderRight = null;

    /**
     * The top border style.
     */
    protected ?PdfCellBorder $borderTop = null;

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
     * @param string|null   $text      the cell text
     * @param int           $cols      the cell columns span
     * @param PdfStyle|null $style     the cell style
     * @param string|null   $alignment the cell alignment
     */
    public function __construct(?string $text = null, int $cols = 1, ?PdfStyle $style = null, ?string $alignment = PdfConstantsInterface::ALIGN_INHERITED)
    {
        $this->setText($text)
            ->setCols($cols)
            ->setStyle($style)
            ->setAlignment($alignment);
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        // deep clone
        if (null !== $this->borderBottom) {
            $this->borderBottom = clone $this->borderBottom;
        }
        if (null !== $this->borderLeft) {
            $this->borderLeft = clone $this->borderLeft;
        }
        if (null !== $this->borderRight) {
            $this->borderRight = clone $this->borderRight;
        }
        if (null !== $this->borderTop) {
            $this->borderTop = clone $this->borderTop;
        }
        if (null !== $this->style) {
            $this->style = clone $this->style;
        }
    }

    /**
     * Gets the bottom border style.
     */
    public function getBorderBottom(): ?PdfCellBorder
    {
        return $this->borderBottom;
    }

    /**
     * Gets the left border style.
     */
    public function getBorderLeft(): ?PdfCellBorder
    {
        return $this->borderLeft;
    }

    /**
     * Gets the right border style.
     */
    public function getBorderRight(): ?PdfCellBorder
    {
        return $this->borderRight;
    }

    /**
     * Gets the top border style.
     */
    public function getBorderTop(): ?PdfCellBorder
    {
        return $this->borderTop;
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
     * Returns if one or more border styles (top, bottom, left, right) are set.
     */
    public function isBorderStyle(): bool
    {
        return isset($this->borderTop)
            || isset($this->borderBottom)
            || isset($this->borderLeft)
            || isset($this->borderRight);
    }

    /**
     * Sets the bottom border style.
     */
    public function setBorderBottom(?PdfCellBorder $borderBottom): self
    {
        $this->borderBottom = $borderBottom;

        return $this;
    }

    /**
     * Sets the left border style.
     */
    public function setBorderLeft(?PdfCellBorder $borderLeft): self
    {
        $this->borderLeft = $borderLeft;

        return $this;
    }

    /**
     * Sets the right border style.
     */
    public function setBorderRight(?PdfCellBorder $borderRight): self
    {
        $this->borderRight = $borderRight;

        return $this;
    }

    /**
     * Sets the top border style.
     */
    public function setBorderTop(?PdfCellBorder $borderTop): self
    {
        $this->borderTop = $borderTop;

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
