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

use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfFontStyle;
use fpdf\PdfDocument;

/**
 * Define a font style.
 */
class PdfFont implements PdfDocumentUpdaterInterface
{
    /**
     * The default font name (Arial).
     */
    public const PdfFontName DEFAULT_NAME = PdfFontName::ARIAL;

    /**
     * The default font size (9pt).
     */
    public const float DEFAULT_SIZE = 9.0;

    /**
     * The default font style (Regular).
     */
    public const PdfFontStyle DEFAULT_STYLE = PdfFontStyle::REGULAR;

    public function __construct(
        private PdfFontName $name = self::DEFAULT_NAME,
        private float $size = self::DEFAULT_SIZE,
        private PdfFontStyle $style = self::DEFAULT_STYLE
    ) {
    }

    #[\Override]
    public function apply(PdfDocument $doc): void
    {
        $doc->setFont($this->name, $this->style, $this->size);
    }

    /**
     * Sets or add the bold font style.
     *
     * @param bool $add true to add bold style to the existing style, false to replace
     */
    public function bold(bool $add = false): self
    {
        return $this->updateStyle(PdfFontStyle::BOLD, $add);
    }

    /**
     * Gets a new instance with default values.
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
     * Returns if this font uses the default size.
     */
    public function isDefaultSize(): bool
    {
        return self::DEFAULT_SIZE === $this->size;
    }

    /**
     * Sets or add the italic font style.
     *
     * @param bool $add true to add italic style to existing style, false to replace
     */
    public function italic(bool $add = false): self
    {
        return $this->updateStyle(PdfFontStyle::ITALIC, $add);
    }

    /**
     * Sets the font style to regular.
     */
    public function regular(): self
    {
        return $this->updateStyle(PdfFontStyle::REGULAR, false);
    }

    /**
     * Reset all properties to the default values.
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
     *
     * @param ?PdfFontName $name the font name to set or null to use the default font name (ARIAL)
     */
    public function setName(?PdfFontName $name = null): self
    {
        $this->name = $name ?? self::DEFAULT_NAME;

        return $this;
    }

    /**
     * Sets the font size.
     *
     * @param ?float $size the font size to set or null to use the default size (9.0).
     *
     * @return self this instance
     */
    public function setSize(?float $size = null): self
    {
        $this->size = $size ?? self::DEFAULT_SIZE;

        return $this;
    }

    /**
     * Sets the font style.
     *
     * @param ?PdfFontStyle $style the font style to set or null to use the default style (Regular)
     */
    public function setStyle(?PdfFontStyle $style = null): self
    {
        $this->style = $style ?? self::DEFAULT_STYLE;

        return $this;
    }

    /**
     * Sets or add the underline font style.
     *
     * @param bool $add true to add underline style to existing style, false to replace
     */
    public function underline(bool $add = false): self
    {
        return $this->updateStyle(PdfFontStyle::UNDERLINE, $add);
    }

    private function updateStyle(PdfFontStyle $style, bool $add): self
    {
        if ($add) {
            $str = $this->style->value . $style->value;
            $this->style = PdfFontStyle::fromString($str);
        } else {
            $this->style = $style;
        }

        return $this;
    }
}
