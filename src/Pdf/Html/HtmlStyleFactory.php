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

/**
 * Factory to create HtmlStyle depending on the tag name.
 */
final class HtmlStyleFactory
{
    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Creates a style for the given tag name.
     */
    public static function create(string $name): ?HtmlStyle
    {
        return match (\strtolower($name)) {
            HtmlConstantsInterface::H1 => self::doCreate(true, 2.5, 2),
            HtmlConstantsInterface::H2 => self::doCreate(true, 2.0, 2),
            HtmlConstantsInterface::H3 => self::doCreate(true, 1.75, 2),
            HtmlConstantsInterface::H4 => self::doCreate(true, 1.5, 2),
            HtmlConstantsInterface::H5 => self::doCreate(true, 1.25, 2),
            HtmlConstantsInterface::H6 => self::doCreate(true, 1.1, 2),
            HtmlConstantsInterface::PARAGRAPH => self::doCreate(false, 1.0, 2),
            HtmlConstantsInterface::LIST_ORDERED,
            HtmlConstantsInterface::LIST_UNORDERED => self::doCreate(false, 1.0, 2, 4),
            HtmlConstantsInterface::LIST_ITEM,
            HtmlConstantsInterface::SPAN => self::default(),
            HtmlConstantsInterface::BOLD,
            HtmlConstantsInterface::STRONG => self::doCreate(true),
            HtmlConstantsInterface::ITALIC,
            HtmlConstantsInterface::EMPHASIS => self::default()->setFontItalic(true),
            HtmlConstantsInterface::UNDERLINE => self::default()->setFontUnderline(true),
            HtmlConstantsInterface::CODE => self::default()->setFontName(PdfFontName::COURIER)->setTextColor(PdfTextColor::red()),
            HtmlConstantsInterface::VARIABLE => self::default()->setFontName(PdfFontName::COURIER)->setFontItalic(),
            HtmlConstantsInterface::SAMPLE,
            HtmlConstantsInterface::KEYBOARD => self::default()->setFontName(PdfFontName::COURIER),
            default => null,
        };
    }

    private static function default(): HtmlStyle
    {
        return new HtmlStyle();
    }

    private static function doCreate(bool $bold = false, float $sizeFactor = 1.0, float $bottomMargin = 0.0, float $leftMargin = 0.0): HtmlStyle
    {
        $font = PdfFont::default()
            ->setStyle($bold ? PdfFontStyle::BOLD : PdfFontStyle::REGULAR)
            ->setSize($sizeFactor * PdfFont::DEFAULT_SIZE);

        return self::default()
            ->setBottomMargin($bottomMargin)
            ->setLeftMargin($leftMargin)
            ->setFont($font);
    }
}
