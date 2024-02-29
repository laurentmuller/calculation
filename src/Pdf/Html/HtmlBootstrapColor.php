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
 * @version 4.4.1
 */
enum HtmlBootstrapColor: string implements PdfColorInterface
{
    use PdfColorTrait;

    case DANGER = '#DC3545';
    case DARK = '#343A40';
    case INFO = '#17A2B8';
    case LIGHT = '#F8F9FA';
    case PRIMARY = '#007BFF';
    case SECONDARY = '#6C757D';
    case SUCCESS = '#28A745';
    case WARNING = '#FFC107';

    /**
     * Gets the border color for the given class name.
     */
    public static function parseBorderColor(string $class): ?PdfDrawColor
    {
        $color = match ($class) {
            'border-primary' => HtmlBootstrapColor::PRIMARY,
            'border-secondary' => HtmlBootstrapColor::SECONDARY,
            'border-success' => HtmlBootstrapColor::SUCCESS,
            'border-danger' => HtmlBootstrapColor::DANGER,
            'border-warning' => HtmlBootstrapColor::WARNING,
            'border-info' => HtmlBootstrapColor::INFO,
            'border-light' => HtmlBootstrapColor::LIGHT,
            'border-dark' => HtmlBootstrapColor::DARK,
            default => null,
        };

        return $color?->getDrawColor();
    }

    /**
     * Gets the fill color for the given class name.
     */
    public static function parseFillColor(string $class): ?PdfFillColor
    {
        $color = match ($class) {
            'bg-primary',
            'text-bg-primary' => HtmlBootstrapColor::PRIMARY,
            'bg-secondary',
            'text-bg-secondary' => HtmlBootstrapColor::SECONDARY,
            'bg-success',
            'text-bg-success' => HtmlBootstrapColor::SUCCESS,
            'bg-danger',
            'text-bg-danger' => HtmlBootstrapColor::DANGER,
            'bg-warning',
            'text-bg-warning' => HtmlBootstrapColor::WARNING,
            'bg-info',
            'text-bg-info' => HtmlBootstrapColor::INFO,
            'bg-light',
            'text-bg-light' => HtmlBootstrapColor::LIGHT,
            'bg-dark',
            'text-bg-dark' => HtmlBootstrapColor::DARK,
            default => null,
        };

        return $color?->getFillColor();
    }

    /**
     * Gets the text color for the given class name.
     */
    public static function parseTextColor(string $class): ?PdfTextColor
    {
        $color = match ($class) {
            'text-primary' => HtmlBootstrapColor::PRIMARY,
            'text-secondary' => HtmlBootstrapColor::SECONDARY,
            'text-success' => HtmlBootstrapColor::SUCCESS,
            'text-danger' => HtmlBootstrapColor::DANGER,
            'text-warning' => HtmlBootstrapColor::WARNING,
            'text-info' => HtmlBootstrapColor::INFO,
            'text-light' => HtmlBootstrapColor::LIGHT,
            'text-dark' => HtmlBootstrapColor::DARK,
            default => null,
        };

        return $color?->getTextColor();
    }
}
