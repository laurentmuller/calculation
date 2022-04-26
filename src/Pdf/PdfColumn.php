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
 * Define a column for a Pdf table.
 *
 * @see \App\Pdf\PdfTable
 */
class PdfColumn
{
    /**
     * The column alignment.
     */
    protected ?PdfTextAlignment $alignment = null;

    /**
     * The fixed width.
     */
    protected bool $fixed = false;

    /**
     * The column's text.
     */
    protected ?string $text = null;

    /**
     * The column's width.
     */
    protected float $width = 0.0;

    /**
     * Constructor.
     *
     * @param string|null           $text      the column text
     * @param float                 $width     the column width
     * @param PdfTextAlignment|null $alignment the column alignment
     * @param bool                  $fixed     true if the column width is fixed. This property is used only if the
     *                                         parent's table use all the document width.
     */
    public function __construct(?string $text, float $width, ?PdfTextAlignment $alignment = PdfTextAlignment::LEFT, bool $fixed = false)
    {
        $this->setText($text)
            ->setWidth($width)
            ->setAlignment($alignment)
            ->setFixed($fixed);
    }

    /**
     * Create a column with center alignment.
     *
     * @param string|null $text  the column text
     * @param float       $width the column width
     * @param bool        $fixed true if the column width is fixed. This property is used only if the
     *                           parent's table use all the document width.
     *
     * @return PdfColumn the new newly created column
     */
    public static function center(?string $text, float $width, bool $fixed = false): self
    {
        return new self($text, $width, PdfTextAlignment::CENTER, $fixed);
    }

    /**
     * Gets the cell alignment.
     */
    public function getAlignment(): ?PdfTextAlignment
    {
        return $this->alignment;
    }

    /**
     * Gets the column text.
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Gets the column width.
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * Gets a value indicating if the column's width is fixed.
     * This property is used only when the parent's table take all the printable width.
     *
     * @return bool true if fixed
     *
     * @see \App\Pdf\PdfTableBuilder::isFullWidth()
     */
    public function isFixed(): bool
    {
        return $this->fixed;
    }

    /**
     * Create a column with left alignment.
     *
     * @param string|null $text  the column text
     * @param float       $width the column width
     * @param bool        $fixed true if the column width is fixed. This property is used only if the
     *                           parent's table use all the document width.
     *
     * @return PdfColumn the new newly created column
     */
    public static function left(?string $text, float $width, bool $fixed = false): self
    {
        return new self($text, $width, PdfTextAlignment::LEFT, $fixed);
    }

    /**
     * Create a column with right alignment.
     *
     * @param string|null $text  the column text
     * @param float       $width the column width
     * @param bool        $fixed true if the column width is fixed. This property is used only if the
     *                           parent's table use all the document width.
     *
     * @return PdfColumn the new newly created column
     */
    public static function right(?string $text, float $width, bool $fixed = false): self
    {
        return new self($text, $width, PdfTextAlignment::RIGHT, $fixed);
    }

    /**
     * Sets the column alignment.
     */
    public function setAlignment(?PdfTextAlignment $alignment): self
    {
        $this->alignment = $alignment;

        return $this;
    }

    /**
     * Sets a value indicating if the column's width is fixed.
     * This property is used only when the parent's table take all the printable width.
     *
     * @param bool $fixed true if fixed
     *
     * @return self this instance
     *
     * @see \App\Pdf\PdfTableBuilder::isFullWidth()
     */
    public function setFixed(bool $fixed): self
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * Sets the column text.
     *
     * @return self this instance
     */
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Sets the column width.
     *
     * @return self this instance
     */
    public function setWidth(float $width): self
    {
        $this->width = $width;

        return $this;
    }
}
