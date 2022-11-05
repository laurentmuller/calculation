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

/**
 * This class describe a style that can be applied to a PDF document.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PdfStyle implements PdfDocumentUpdaterInterface
{
    /**
     * The border style.
     */
    protected PdfBorder $border;

    /**
     * The draw color.
     */
    protected PdfDrawColor $drawColor;

    /**
     * The fill color.
     */
    protected PdfFillColor $fillColor;

    /**
     * The font.
     */
    protected PdfFont $font;

    /**
     * The left indent.
     */
    protected int $indent = 0;

    /**
     * The line.
     */
    protected PdfLine $line;

    /**
     * The text color.
     */
    protected PdfTextColor $textColor;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    public function __clone()
    {
        // deep clone
        $this->drawColor = clone $this->drawColor;
        $this->fillColor = clone $this->fillColor;
        $this->textColor = clone $this->textColor;
        $this->border = clone $this->border;
        $this->font = clone $this->font;
        $this->line = clone $this->line;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $this->font->apply($doc);
        $this->line->apply($doc);
        $this->drawColor->apply($doc);
        $this->fillColor->apply($doc);
        $this->textColor->apply($doc);
    }

    /**
     * Gets the black header cell style. The style has the following properties:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Bold</td></tr>
     * <tr><td>Line width</td><td>:</td><td>0.2mm</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Text color</td><td>:</td><td>White</i></td></tr>
     * </table>.
     */
    public static function getBlackHeaderStyle(): self
    {
        return self::getCellStyle()
            ->setFillColor(PdfFillColor::black())
            ->setDrawColor(PdfDrawColor::black())
            ->setTextColor(PdfTextColor::white())
            ->setFontBold();
    }

    /**
     * Gets the bold cell style. The style has the following properties:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Bold</td></tr>
     * <tr><td>Line width</td><td>:</td><td>0.2mm</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>White</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>RGB(221, 221, 221)</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Black</i></td></tr>
     * </table>.
     */
    public static function getBoldCellStyle(): self
    {
        return self::getCellStyle()
            ->setFontBold();
    }

    /**
     * Gets the border.
     */
    public function getBorder(): PdfBorder
    {
        return $this->border;
    }

    /**
     * Gets the cell style. The style has the following properties:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Regular</td></tr>
     * <tr><td>Line width</td><td>:</td><td>0.2mm</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>White</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>RGB(221, 221, 221)</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Black</i></td></tr>
     * </table>.
     */
    public static function getCellStyle(): self
    {
        return self::getDefaultStyle()
            ->setFillColor(PdfFillColor::white())
            ->setDrawColor(PdfDrawColor::cellBorder());
    }

    /**
     * Gets the default style. The style has the following properties:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Regular</td></tr>
     * <tr><td>Line width</td><td>:</td><td>0.2mm</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>White</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Black</td></tr>
     * </table>.
     */
    public static function getDefaultStyle(): self
    {
        return new self();
    }

    /**
     * Gets the draw color.
     */
    public function getDrawColor(): PdfDrawColor
    {
        return $this->drawColor;
    }

    /**
     * Gets the fill color.
     */
    public function getFillColor(): PdfFillColor
    {
        return $this->fillColor;
    }

    /**
     * Gets the font.
     */
    public function getFont(): PdfFont
    {
        return $this->font;
    }

    /**
     * Gets the header cell style. The style has the following properties:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Bold</td></tr>
     * <tr><td>Line width</td><td>:</td><td>0.2mm</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>RGB(245, 245, 245)</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>RGB(221, 221, 221)</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Black</i></td></tr>
     * </table>.
     */
    public static function getHeaderStyle(): self
    {
        return self::getCellStyle()
            ->setFillColor(PdfFillColor::header())
            ->setFontBold();
    }

    /**
     * Gets the left indent.
     */
    public function getIndent(): int
    {
        return $this->indent;
    }

    /**
     * Gets the line.
     */
    public function getLine(): PdfLine
    {
        return $this->line;
    }

    /**
     * Gets the link style. The style has the following properties:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Regular</td></tr>
     * <tr><td>Line width</td><td>:</td><td>0.2mm</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>White</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Blue</td></tr>
     * </table>.
     */
    public static function getLinkStyle(): self
    {
        return self::getDefaultStyle()
            ->setTextColor(PdfTextColor::link());
    }

    /**
     * Gets the no border style. The style has the following properties:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Regular</td></tr>
     * <tr><td>Border</td><td>:</td><td>None</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>White</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Black</td></tr>
     * </table>.
     */
    public static function getNoBorderStyle(): self
    {
        return self::getDefaultStyle()
            ->setBorder(PdfBorder::NONE);
    }

    /**
     * Gets the text color.
     */
    public function getTextColor(): PdfTextColor
    {
        return $this->textColor;
    }

    /**
     * Gets a value indicating if the fill color is set.
     * To be true, the fill color must be different from the White color.
     *
     * @return bool true if the fill color is set
     */
    public function isFillColor(): bool
    {
        return $this->fillColor->isFillColor();
    }

    /**
     * Reset all properties to the default values. The default values are:
     * <table style='padding:15px'>
     * <tr><td>Font</td><td>:</td><td>Arial 9pt Regular</td></tr>
     * <tr><td>Line width</td><td>:</td><td>0.2mm</td></tr>
     * <tr><td>Fill color</td><td>:</td><td>White</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Indent</td><td>:</td><td>0 mm</td></tr>
     * </table>.
     */
    public function reset(): static
    {
        return $this->resetLine()
            ->resetBorder()
            ->resetColors()
            ->resetFont()
            ->resetIndent();
    }

    /**
     * Sets border to default (none).
     */
    public function resetBorder(): static
    {
        return $this->setBorder(PdfBorder::ALL);
    }

    /**
     * Sets colors properties to the default values. The default colors are:
     * <table style='padding:15px'>
     * <tr><td>Fill color</td><td>:</td><td>White</td></tr>
     * <tr><td>Draw color</td><td>:</td><td>Black</td></tr>
     * <tr><td>Text color</td><td>:</td><td>Black</td></tr>
     * </table>.
     */
    public function resetColors(): static
    {
        return $this->setFillColor(PdfFillColor::white())
            ->setDrawColor(PdfDrawColor::black())
            ->setTextColor(PdfTextColor::black());
    }

    /**
     * Sets font to the default value. The default value is "<code>Arial, 9pt, regular</code>".
     */
    public function resetFont(): static
    {
        return $this->setFont(PdfFont::default());
    }

    /**
     * Sets the left indent to the default value. The default value is "<code>0 mm</code>".
     */
    public function resetIndent(): static
    {
        return $this->setIndent(0);
    }

    /**
     * Sets the line width property to the default value. The default line width is "<code>0.2 mm</code>".
     */
    public function resetLine(): static
    {
        return $this->setLine(PdfLine::default());
    }

    /**
     * Sets the border.
     */
    public function setBorder(PdfBorder|string|int $border): static
    {
        $this->border = \is_string($border) || \is_int($border) ? new PdfBorder($border) : $border;

        return $this;
    }

    /**
     * Sets the draw color.
     */
    public function setDrawColor(PdfDrawColor $drawColor): static
    {
        $this->drawColor = $drawColor;

        return $this;
    }

    /**
     * Sets the fill color.
     */
    public function setFillColor(PdfFillColor $fillColor): static
    {
        $this->fillColor = $fillColor;

        return $this;
    }

    /**
     * Sets the font style.
     */
    public function setFont(PdfFont $font): static
    {
        $this->font = $font;

        return $this;
    }

    /**
     * Sets the font style to bold.
     *
     * @param bool $add true to add the bold style to the existing style; false to replace
     */
    public function setFontBold(bool $add = false): static
    {
        $style = PdfFont::STYLE_BOLD;
        if ($add) {
            $style .= $this->font->getStyle();
        }

        return $this->setFontStyle($style);
    }

    /**
     * Sets the font style to italic.
     *
     * @param bool $add true to add the italic style to the existing style; false to replace
     */
    public function setFontItalic(bool $add = false): static
    {
        $style = PdfFont::STYLE_ITALIC;
        if ($add) {
            $style .= $this->font->getStyle();
        }

        return $this->setFontStyle($style);
    }

    /**
     * Sets the font name.
     * Can be one of the FONT_NAME_XX constants.
     */
    public function setFontName(string $fontName): static
    {
        $this->font->setName($fontName);

        return $this;
    }

    /**
     * Sets the font style to regular (default).
     */
    public function setFontRegular(): static
    {
        $this->font->regular();

        return $this;
    }

    /**
     * Sets the font size in points.
     */
    public function setFontSize(float $fontSize): static
    {
        $this->font->setSize($fontSize);

        return $this;
    }

    /**
     * Sets the font style.
     * Can be one of the FONT_STYLE_XX constants or any combination.
     */
    public function setFontStyle(string $fontStyle): static
    {
        $this->font->setStyle($fontStyle);

        return $this;
    }

    /**
     * Sets the font style to underline.
     *
     * @param bool $add true to add the underline style to the existing style; false to replace
     */
    public function setFontUnderline(bool $add = false): static
    {
        $style = PdfFont::STYLE_UNDERLINE;
        if ($add) {
            $style .= $this->font->getStyle();
        }

        return $this->setFontStyle($style);
    }

    /**
     * Sets the left indent.
     */
    public function setIndent(int $indent): static
    {
        $this->indent = \max($indent, 0);

        return $this;
    }

    /**
     * Sets the line.
     */
    public function setLine(PdfLine $line): static
    {
        $this->line = $line;

        return $this;
    }

    /**
     * Sets the text color.
     */
    public function setTextColor(PdfTextColor $textColor): static
    {
        $this->textColor = $textColor;

        return $this;
    }
}
