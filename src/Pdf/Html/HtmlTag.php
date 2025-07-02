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

namespace App\Pdf\Html;

use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfFont;
use App\Traits\EnumExtrasTrait;
use App\Utils\StringUtils;
use Elao\Enum\Attribute\EnumCase;
use fpdf\Enums\PdfFontName;

/**
 * Html tag enumeration.
 */
enum HtmlTag: string
{
    use EnumExtrasTrait;

    /**
     * The body tag name.
     */
    #[EnumCase(extras: ['style' => false])]
    case BODY = 'body';

    /**
     * The bold element.
     */
    #[EnumCase(extras: ['font-bold' => true])]
    case BOLD = 'b';

    /**
     * The inline code element.
     */
    #[EnumCase(extras: ['font-name' => 'courier', 'text-color' => '#FF0000'])]
    case CODE = 'code';
    /**
     * The description details tag name.
     */
    #[EnumCase(extras: ['bottom-margin' => 2.0])]
    case DESCRIPTION_DETAIL = 'dd';

    /**
     * The description list tag name.
     */
    case DESCRIPTION_LIST = 'dl';

    /**
     * The description term tag name.
     */
    #[EnumCase(extras: ['font-bold' => true])]
    case DESCRIPTION_TERM = 'dt';

    /**
     * The emphasis element.
     */
    #[EnumCase(extras: ['font-italic' => true])]
    case EMPHASIS = 'em';

    /**
     * The H1 tag name.
     */
    #[EnumCase(extras: ['font-bold' => true, 'font-size' => 2.5, 'bottom-margin' => 2.0])]
    case H1 = 'h1';

    /**
     * The H2 tag name.
     */
    #[EnumCase(extras: ['font-bold' => true, 'font-size' => 2.0, 'bottom-margin' => 2.0])]
    case H2 = 'h2';

    /**
     * The H3 tag name.
     */
    #[EnumCase(extras: ['font-bold' => true, 'font-size' => 1.75, 'bottom-margin' => 2.0])]
    case H3 = 'h3';

    /**
     * The H4 tag name.
     */
    #[EnumCase(extras: ['font-bold' => true, 'font-size' => 1.5, 'bottom-margin' => 2.0])]
    case H4 = 'h4';

    /**
     * The H5 tag name.
     */
    #[EnumCase(extras: ['font-bold' => true, 'font-size' => 1.25, 'bottom-margin' => 2.0])]
    case H5 = 'h5';

    /**
     * The H6 tag name.
     */
    #[EnumCase(extras: ['font-bold' => true, 'font-size' => 1.1, 'bottom-margin' => 2.0])]
    case H6 = 'h6';

    /**
     * The italic element.
     */
    #[EnumCase(extras: ['font-italic' => true])]
    case ITALIC = 'i';

    /**
     * The keyboard input element.
     */
    #[EnumCase(extras: ['font-name' => 'courier'])]
    case KEYBOARD = 'kbd';

    /**
     * The line-break tag name.
     */
    #[EnumCase(extras: ['style' => false])]
    case LINE_BREAK = 'br';

    /**
     * The list item tag name.
     */
    #[EnumCase(extras: ['style' => false])]
    case LIST_ITEM = 'li';

    /**
     * The ordered list tag name.
     */
    #[EnumCase(extras: ['bottom-margin' => 1.0, 'left-margin' => 2.0])]
    case LIST_ORDERED = 'ol';

    /**
     * The unordered list tag name.
     */
    #[EnumCase(extras: ['bottom-margin' => 1.0, 'left-margin' => 2.0])]
    case LIST_UNORDERED = 'ul';

    /**
     * The page-break class name.
     */
    #[EnumCase(extras: ['style' => false])]
    case PAGE_BREAK = 'page-break';

    /**
     * The paragraph tag name.
     */
    #[EnumCase(extras: ['bottom-margin' => 2.0])]
    case PARAGRAPH = 'p';
    /**
     * The sample output element.
     */
    #[EnumCase(extras: ['font-name' => 'courier'])]
    case SAMPLE = 'samp';

    /**
     * The span tag name.
     */
    #[EnumCase(extras: ['style' => false])]
    case SPAN = 'span';

    /**
     * The strong importance element.
     */
    #[EnumCase(extras: ['font-bold' => true])]
    case STRONG = 'strong';

    /**
     * The text chunk.
     */
    #[EnumCase(extras: ['style' => false])]
    case TEXT = '#text';

    /*
     * The underline element.
     */
    #[EnumCase(extras: ['font-underline' => true])]
    case UNDERLINE = 'u';

    /**
     * The variable element.
     */
    #[EnumCase(extras: ['font-name' => 'courier', 'font-italic' => true])]
    case VARIABLE = 'var';

    /**
     * Find the first node for this tag value.
     */
    public function findFirst(\DOMDocument $document): ?\DOMNode
    {
        $elements = $document->getElementsByTagName($this->value);
        if (0 !== $elements->length) {
            return $elements->item(0);
        }

        return null;
    }

    /**
     * Gets the font size, if applicable; the default size otherwise.
     */
    public function getFontSize(): float
    {
        return PdfFont::DEFAULT_SIZE * $this->getExtraFloat('font-size', 1.0);
    }

    /**
     * Creates a style for the given tag name, ignoring case consideration.
     */
    public static function getStyle(string $value): ?HtmlStyle
    {
        return HtmlTag::tryFrom(\strtolower($value))?->style();
    }

    /**
     * Returns a value indicating if the given value is equal to this value, ignoring case consideration.
     */
    public function match(string $value): bool
    {
        return StringUtils::equalIgnoreCase($this->value, $value);
    }

    /**
     * Gets the style for this tag.
     */
    public function style(): ?HtmlStyle
    {
        if (!$this->getExtraBool('style', true)) {
            return null;
        }

        $style = new HtmlStyle();
        $style->setBottomMargin($this->getExtraFloat('bottom-margin'))
            ->setLeftMargin($this->getExtraFloat('left-margin'))
            ->setFont($this->getFont());

        $color = $this->getTextColor();
        if ($color instanceof PdfTextColor) {
            $style->setTextColor($color);
        }

        return $style;
    }

    private function getFont(): PdfFont
    {
        $font = PdfFont::default();
        $fontName = PdfFontName::tryFromFamily($this->getExtraString('font-name'));
        if ($fontName instanceof PdfFontName) {
            $font->setName($fontName);
        }
        $fontSize = $this->getFontSize();
        if (PdfFont::DEFAULT_SIZE !== $fontSize) {
            $font->setSize($fontSize);
        }
        if ($this->getExtraBool('font-bold')) {
            $font->bold(true);
        }
        if ($this->getExtraBool('font-italic')) {
            $font->italic(true);
        }
        if ($this->getExtraBool('font-underline')) {
            $font->underline(true);
        }

        return $font;
    }

    private function getTextColor(): ?PdfTextColor
    {
        $textColor = $this->getExtraString('text-color');
        if ('' === $textColor) {
            return null;
        }

        return PdfTextColor::create($textColor);
    }
}
