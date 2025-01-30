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

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Interfaces\PdfColorInterface;
use App\Pdf\Traits\PdfColorTrait;

/**
 * Bootstrap color enumeration.
 *
 * Picked from version 5.3.
 */
enum HtmlBootstrapColor: string implements PdfColorInterface
{
    use PdfColorTrait;

    case DANGER = '#DC3545';
    case DARK = '#212529';
    case INFO = '#0DCAF0';
    case LIGHT = '#F8F9FA';
    case PRIMARY = '#0D6EFD';
    case SECONDARY = '#6C757D';
    case SUCCESS = '#198754';
    case WARNING = '#FFC107';

    /**
     * Gets the draw color for the given class name.
     */
    public static function parseDrawColor(string $class): ?PdfDrawColor
    {
        return HtmlBootstrapColor::parseClass($class)?->getDrawColor();
    }

    /**
     * Gets the fill color for the given class name.
     */
    public static function parseFillColor(string $class): ?PdfFillColor
    {
        return HtmlBootstrapColor::parseClass($class)?->getFillColor();
    }

    /**
     * Gets the text color for the given class name.
     */
    public static function parseTextColor(string $class): ?PdfTextColor
    {
        return HtmlBootstrapColor::parseClass($class)?->getTextColor();
    }

    private static function parseClass(string $class): ?HtmlBootstrapColor
    {
        $values = \explode('-', \strtolower($class));

        return match (\end($values)) {
            'primary' => HtmlBootstrapColor::PRIMARY,
            'secondary' => HtmlBootstrapColor::SECONDARY,
            'success' => HtmlBootstrapColor::SUCCESS,
            'danger' => HtmlBootstrapColor::DANGER,
            'warning' => HtmlBootstrapColor::WARNING,
            'info' => HtmlBootstrapColor::INFO,
            'light' => HtmlBootstrapColor::LIGHT,
            'dark' => HtmlBootstrapColor::DARK,
            default => null,
        };
    }
}
