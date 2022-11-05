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

use App\Pdf\PdfFont;
use App\Pdf\PdfTextColor;

/**
 * Factory to create HtmlStyle depenging of the tag name.
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
     *
     * @param string $name the tag name
     *
     * @return ?HtmlStyle the style, if applicable; <code>null</code> otherwise
     */
    public static function create(string $name): ?HtmlStyle
    {
        return match (\strtolower($name)) {
            'h1' => self::doCreate(true, PdfFont::DEFAULT_SIZE * 2.5, 2),
            'h2' => self::doCreate(true, PdfFont::DEFAULT_SIZE * 2, 2),
            'h3' => self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.75, 2),
            'h4' => self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.5, 2),
            'h5' => self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.25, 2),
            'h6' => self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.1, 2),
            'p' => self::doCreate(false, PdfFont::DEFAULT_SIZE, 2),
            'ul', 'ol' => self::doCreate(false, PdfFont::DEFAULT_SIZE, 2, 4),
            'li' => self::default(),
            'b', 'strong' => self::doCreate(true),
            'i', 'em' => self::default()->italic(true),
            'u' => self::default()->underline(true),
            'code' => self::default()
                ->setTextColor(PdfTextColor::red())
                ->setFontName(PdfFont::NAME_COURIER),
            'var' => self::default()
                ->setFontName(PdfFont::NAME_COURIER)
                ->setFontItalic(),
            'samp', 'kbd' => self::default()
                ->setFontName(PdfFont::NAME_COURIER),
            default => null,
        };
    }

    /**
     * Gets the default style.
     */
    public static function default(): HtmlStyle
    {
        return new HtmlStyle();
    }

    /**
     * Creates a new style.
     *
     * @param bool  $bold         the font bold
     * @param float $size         the font size
     * @param float $bottomMargin the bottom margin
     * @param float $leftMargin   the left margin
     */
    private static function doCreate(bool $bold = false, float $size = PdfFont::DEFAULT_SIZE, float $bottomMargin = 0.0, float $leftMargin = 0.0): HtmlStyle
    {
        $font = PdfFont::default()
            ->setStyle($bold ? PdfFont::STYLE_BOLD : PdfFont::STYLE_REGULAR)
            ->setSize($size);

        $result = self::default();
        $result->setBottomMargin($bottomMargin)
            ->setLeftMargin($leftMargin)
            ->setFont($font);

        return $result;
    }
}
