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

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Enums\PdfFontName;
use App\Pdf\Enums\PdfFontStyle;
use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;

/**
 * This class describe a style that can be applied to a PDF document.
 */
class PdfStyle implements PdfDocumentUpdaterInterface
{
    /**
     * The bullet character (0xB7 = 183).
     *
     * @see PdfStyle::getBulletStyle()
     */
    public const BULLET = '·';

    /**
     * The border style.
     */
    private PdfBorder $border;

    /**
     * The draw color.
     */
    private PdfDrawColor $drawColor;

    /**
     * The fill color.
     */
    private PdfFillColor $fillColor;

    /**
     * The font.
     */
    private PdfFont $font;

    /**
     * The left indent.
     */
    private float $indent = 0.0;

    /**
     * The line.
     */
    private PdfLine $line;

    /**
     * The text color.
     */
    private PdfTextColor $textColor;

    public function __construct()
    {
        $this->font = PdfFont::default();
        $this->line = PdfLine::default();
        $this->border = PdfBorder::default();
        $this->textColor = PdfTextColor::default();
        $this->drawColor = PdfDrawColor::default();
        $this->fillColor = PdfFillColor::default();
    }

    public function __clone()
    {
        // deep clone
        $this->font = clone $this->font;
        $this->line = clone $this->line;
        $this->border = clone $this->border;
        $this->textColor = clone $this->textColor;
        $this->drawColor = clone $this->drawColor;
        $this->fillColor = clone $this->fillColor;
    }

    public function apply(PdfDocument $doc): void
    {
        $this->font->apply($doc);
        $this->line->apply($doc);
        $this->drawColor->apply($doc);
        $this->fillColor->apply($doc);
        $this->textColor->apply($doc);
    }

    /**
     * Gets the default style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9pt Regular.
     * - Line width: 0.2mm.
     * - Fill color: White.
     * - Draw color: Black.
     * - Text color: Black.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Gets the black header cell style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9pt Bold.
     * - Line width: 0.2mm.
     * - Fill color: Black.
     * - Draw color: Black.
     * - Text color: White.
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
     * Gets the bold cell style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9pt Bold.
     * - Line width: 0.2mm.
     * - Fill color: White.
     * - Draw color: RGB(221, 221, 221).
     * - Text color: Black.
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
     * Gets the bullet style.
     *
     * The style has the following properties:
     *
     * - Font: Symbol 9pt.
     * - Line width: 0.2mm.
     * - Fill color: White.
     * - Draw color: RGB(221, 221, 221).
     * - Text color: Black.
     *
     * @param PdfStyle|null $source the source style to update or null to use the cell style
     *
     * @see PdfStyle::BULLET
     */
    public static function getBulletStyle(self $source = null): self
    {
        $source ??= self::getCellStyle();

        return $source->setFontName(PdfFontName::SYMBOL);
    }

    /**
     * Gets the cell style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9pt Regular.
     * - Line width: 0.2mm.
     * - Fill color: White.
     * - Draw color: RGB(221, 221, 221).
     * - Text color: Black.
     */
    public static function getCellStyle(): self
    {
        return self::default()
            ->setDrawColor(PdfDrawColor::cellBorder());
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
     * Gets the header cell style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9pt Bold.
     * - Line width: 0.2mm.
     * - Fill color: RGB(245, 245, 245).
     * - Draw color: RGB(221, 221, 221).
     * - Text color: Black.
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
    public function getIndent(): float
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
     * Gets the link style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9pt Regular.
     * - Line width: 0.2mm.
     * - Fill color: White.
     * - Draw color: Black.
     * - Text color: Blue.
     */
    public static function getLinkStyle(): self
    {
        return self::default()
            ->setTextColor(PdfTextColor::link());
    }

    /**
     * Gets the no border style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9pt Regular.
     * - Border: None.
     * - Fill color: White.
     * - Draw color: Black.
     * - Text color: Black.
     */
    public static function getNoBorderStyle(): self
    {
        return self::default()
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
     *
     * To be true, the fill color must be different from the White color.
     *
     * @return bool true if the fill color is set
     */
    public function isFillColor(): bool
    {
        return $this->fillColor->isFillColor();
    }

    /**
     * Reset all properties to the default values.
     *
     * The default values are:
     *
     * - Font: Arial 9pt Regular.
     * - Line width: 0.2mm.
     * - Fill color: White.
     * - Draw Color: Black.
     * - Text Color: Black.
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
     * Sets colors properties to the default values.
     *
     * The default colors are:
     *
     * - Fill color: White.
     * - Draw color: Black.
     * - Text color: Black.
     */
    public function resetColors(): static
    {
        return $this->setFillColor(PdfFillColor::default())
            ->setDrawColor(PdfDrawColor::default())
            ->setTextColor(PdfTextColor::default());
    }

    /**
     * Sets font to the default value.
     *
     * The default value is:
     *
     * - Arial, 9pt, regular.
     */
    public function resetFont(): static
    {
        return $this->setFont(PdfFont::default());
    }

    /**
     * Sets the left indent to the default value.
     *
     * The default value is:
     *
     * - 0 mm.
     */
    public function resetIndent(): static
    {
        return $this->setIndent(0);
    }

    /**
     * Sets the line width property to the default value.
     *
     * The default line width is:
     *
     * - 0.2 mm.
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
        $this->font->bold($add);

        return $this;
    }

    /**
     * Sets the font style to italic.
     *
     * @param bool $add true to add the italic style to the existing style; false to replace
     */
    public function setFontItalic(bool $add = false): static
    {
        $this->font->italic($add);

        return $this;
    }

    /**
     * Sets the font name.
     *
     * @param ?PdfFontName $name the font name or null to use the default name ("ARIAL")
     */
    public function setFontName(PdfFontName $name = null): static
    {
        $this->font->setName($name);

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
     *
     * @param ?PdfFontStyle $style the font style or null to use the default style ("Regular")
     */
    public function setFontStyle(PdfFontStyle $style = null): static
    {
        $this->font->setStyle($style);

        return $this;
    }

    /**
     * Sets the font style to underline.
     *
     * @param bool $add true to add the underline style to the existing style; false to replace
     */
    public function setFontUnderline(bool $add = false): static
    {
        $this->font->underline($add);

        return $this;
    }

    /**
     * Sets the left indent.
     */
    public function setIndent(float $indent): static
    {
        $this->indent = \max($indent, 0.0);

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
