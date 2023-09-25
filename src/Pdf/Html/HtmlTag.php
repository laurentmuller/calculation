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

use App\Pdf\Enums\PdfFontName;
use App\Pdf\Enums\PdfFontStyle;
use App\Pdf\PdfFont;
use App\Pdf\PdfTextColor;
use App\Utils\StringUtils;

/**
 * Html tag enumeration.
 */
enum HtmlTag: string
{
    /**
     * The body tag name.
     */
    case BODY = 'body';

    /**
     * The bold element.
     */
    case BOLD = 'b';

    /**
     * The inline code element.
     */
    case CODE = 'code';

    /**
     * The emphasis element.
     */
    case EMPHASIS = 'em';

    /**
     * The H1 tag name.
     */
    case H1 = 'h1';

    /**
     * The H2 tag name.
     */
    case H2 = 'h2';

    /**
     * The H3 tag name.
     */
    case H3 = 'h3';

    /**
     * The H4 tag name.
     */
    case H4 = 'h4';

    /**
     * The H5 tag name.
     */
    case H5 = 'h5';

    /**
     * The H6 tag name.
     */
    case H6 = 'h6';

    /**
     * The italic element.
     */
    case ITALIC = 'i';

    /**
     * The keyboard input element.
     */
    case KEYBOARD = 'kbd';

    /**
     * The line break tag name.
     */
    case LINE_BREAK = 'br';

    /**
     * The list item tag name.
     */
    case LIST_ITEM = 'li';

    /**
     * The ordered list tag name.
     */
    case LIST_ORDERED = 'ol';

    /**
     * The unordered list tag name.
     */
    case LIST_UNORDERED = 'ul';

    /**
     * The page break class name.
     */
    case PAGE_BREAK = 'page-break';

    /**
     * The paragraph tag name.
     */
    case PARAGRAPH = 'p';

    /**
     * The sample output element.
     */
    case SAMPLE = 'samp';

    /**
     * The span tag name.
     */
    case SPAN = 'span';

    /**
     * The strong importance element.
     */
    case STRONG = 'strong';

    /**
     * The text chunk.
     */
    case TEXT = '#text';

    /*
     * The underline element.
     */
    case UNDERLINE = 'u';

    /**
     * The variable element.
     */
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
     * Gets the style for this tag.
     */
    public function getStyle(): ?HtmlStyle
    {
        return match ($this) {
            HtmlTag::H1 => $this->createStyle(true, 2.5, 2),
            HtmlTag::H2 => $this->createStyle(true, 2.0, 2),
            HtmlTag::H3 => $this->createStyle(true, 1.75, 2),
            HtmlTag::H4 => $this->createStyle(true, 1.5, 2),
            HtmlTag::H5 => $this->createStyle(true, 1.25, 2),
            HtmlTag::H6 => $this->createStyle(true, 1.1, 2),
            HtmlTag::PARAGRAPH => $this->createStyle(false, 1.0, 2),
            HtmlTag::LIST_ORDERED,
            HtmlTag::LIST_UNORDERED => $this->createStyle(false, 1.0, 2, 4),
            HtmlTag::LIST_ITEM,
            HtmlTag::SPAN => $this->createStyle(),
            HtmlTag::BOLD,
            HtmlTag::STRONG => $this->createStyle(true),
            HtmlTag::ITALIC,
            HtmlTag::EMPHASIS => $this->createStyle()->setFontItalic(true),
            HtmlTag::UNDERLINE => $this->createStyle()->setFontUnderline(true),
            HtmlTag::CODE => $this->createStyle()->setFontName(PdfFontName::COURIER)->setTextColor(PdfTextColor::red()),
            HtmlTag::VARIABLE => $this->createStyle()->setFontName(PdfFontName::COURIER)->setFontItalic(),
            HtmlTag::SAMPLE,
            HtmlTag::KEYBOARD => $this->createStyle()->setFontName(PdfFontName::COURIER),
            default => null
        };
    }

    /**
     * Creates a style for the given tag name, ignoring case consideration.
     */
    public static function getStyleFromName(string $value): ?HtmlStyle
    {
        return HtmlTag::tryFrom(\strtolower($value))?->getStyle();
    }

    /**
     * Returns a value indicating if the given value is equal to this value, ignoring case consideration.
     */
    public function match(string $value): bool
    {
        return StringUtils::equalIgnoreCase($this->value, $value);
    }

    private function createStyle(bool $bold = false, float $sizeFactor = 1.0, float $bottomMargin = 0.0, float $leftMargin = 0.0): HtmlStyle
    {
        $style = new HtmlStyle();
        if ($bold || 1.0 !== $sizeFactor) {
            $font = PdfFont::default()
                ->setStyle($bold ? PdfFontStyle::BOLD : PdfFontStyle::REGULAR)
                ->setSize($sizeFactor * PdfFont::DEFAULT_SIZE);
            $style->setFont($font);
        }

        return $style
            ->setBottomMargin($bottomMargin)
            ->setLeftMargin($leftMargin);
    }
}
