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

use App\Pdf\Enums\PdfFontName;
use App\Pdf\Enums\PdfFontStyle;
use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;

/**
 * Define a font style.
 */
class PdfFont implements PdfDocumentUpdaterInterface
{
    /**
     * The default font name (Arial).
     */
    final public const DEFAULT_NAME = PdfFontName::ARIAL;

    /**
     * The default font size (9pt).
     */
    final public const DEFAULT_SIZE = 9.0;

    /**
     * The default font style (Regular).
     */
    final public const DEFAULT_STYLE = PdfFontStyle::REGULAR;

    public function __construct(
        private PdfFontName $name = self::DEFAULT_NAME,
        private float $size = self::DEFAULT_SIZE,
        private PdfFontStyle $style = self::DEFAULT_STYLE
    ) {
    }

    /**
     * Adds the given style, if not present, to this style.
     */
    public function addStyle(PdfFontStyle $style): self
    {
        $newStyle = $this->style->value . $style->value;
        $this->style = PdfFontStyle::fromStyle($newStyle);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $doc->SetFont($this->name, $this->style, $this->size);
    }

    /**
     * Sets the font style to bold.
     *
     * @param bool $add true to add bold style to existing style, false to replace
     */
    public function bold(bool $add = false): self
    {
        $style = PdfFontStyle::BOLD;

        return $add ? $this->addStyle($style) : $this->setStyle($style);
    }

    /**
     * Gets the default font.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Gets the font name.
     */
    public function getName(): PdfFontName
    {
        return $this->name;
    }

    /**
     * Gets the font size.
     */
    public function getSize(): float
    {
        return $this->size;
    }

    /**
     * Gets the font style.
     */
    public function getStyle(): PdfFontStyle
    {
        return $this->style;
    }

    /**
     * Returns if this font use the default size.
     */
    public function isDefaultSize(): bool
    {
        return self::DEFAULT_SIZE === $this->size;
    }

    /**
     * Sets the font style to italic.
     *
     * @param bool $add true to add italic style to existing style, false to replace
     */
    public function italic(bool $add = false): self
    {
        $style = PdfFontStyle::ITALIC;

        return $add ? $this->addStyle($style) : $this->setStyle($style);
    }

    /**
     * Sets the font style to regular.
     */
    public function regular(): self
    {
        return $this->setStyle(PdfFontStyle::REGULAR);
    }

    /**
     * Reset all properties to the default values.
     *
     * @return self this instance
     */
    public function reset(): self
    {
        $this->name = self::DEFAULT_NAME;
        $this->style = self::DEFAULT_STYLE;
        $this->size = self::DEFAULT_SIZE;

        return $this;
    }

    /**
     * Sets the font name.
     */
    public function setName(?PdfFontName $name = null): self
    {
        $this->name = $name ?? self::DEFAULT_NAME;

        return $this;
    }

    /**
     * Sets the font size.
     *
     * @param float $size the size to set
     *
     * @return self this instance
     */
    public function setSize(float $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Sets the font style.
     */
    public function setStyle(PdfFontStyle $style): self
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Sets the font style to underline.
     *
     * @param bool $add true to add underline style to existing style, false to replace
     */
    public function underline(bool $add = false): self
    {
        $style = PdfFontStyle::UNDERLINE;

        return $add ? $this->addStyle($style) : $this->setStyle($style);
    }
}
