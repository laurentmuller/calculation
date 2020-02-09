<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pdf\Html;

use App\Pdf\PdfFont;
use App\Pdf\PdfTextColor;

/**
 * Factory to create HtmlStyle depenging of the tag name.
 *
 * @author Laurent Muller
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
     * @return HtmlStyle|null the style, if applicable; <code>null</code> otherwise
     */
    public static function create(string $name): ?HtmlStyle
    {
        switch ($name) {
            case 'h1':
                return self::doCreate(true, PdfFont::DEFAULT_SIZE * 2.5, 2);
            case 'h2':
                return self::doCreate(true, PdfFont::DEFAULT_SIZE * 2, 2);
            case 'h3':
                return self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.75, 2);
            case 'h4':
                return self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.5, 2);
            case 'h5':
                return self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.25, 2);
            case 'h6':
                return self::doCreate(true, PdfFont::DEFAULT_SIZE * 1.1, 2);
            case 'p':
                return self::doCreate(false, PdfFont::DEFAULT_SIZE, 2);
            case 'ul':
            case 'ol':
                return self::doCreate(false, PdfFont::DEFAULT_SIZE, 2, 8);
            case 'li':
                return self::default();
            case 'b':
            case 'strong':
                return self::doCreate(true);
            case 'i':
            case 'em':
                return self::default()->italic(true);
            case 'u':
                return self::default()->underline(true);
            //case 'pre':
                //self::default()->setFontName(PdfFont::NAME_COURIER);
            // case 'em':
            case 'code':
                return self::default()
                    ->setTextColor(PdfTextColor::red())
                    ->setFontName(PdfFont::NAME_COURIER);
            case 'var':
                return self::default()
                    ->setFontName(PdfFont::NAME_COURIER)
                    ->setFontItalic();
            case 'samp':
            case 'kbd':
                return self::default()
                    ->setFontName(PdfFont::NAME_COURIER);
//             case 'kbd':
//                 $style = self::default();
//                 return $style->setFontName(PdfFont::NAME_COURIER)
//                     ->setFillColor(PdfFillColor::black())
//                     ->setTextColor(PdfTextColor::white());

            default:
                return null;
        }
    }

    /**
     * Gets the default style.
     *
     * @return \App\Pdf\Html\HtmlStyle
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

        return self::default()
            ->setBottomMargin($bottomMargin)
            ->setLeftMargin($leftMargin)
            ->setFont($font);
    }
}
