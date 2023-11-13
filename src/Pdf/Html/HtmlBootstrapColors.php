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
use App\Pdf\PdfDocument;

/**
 * Bootstrap color enumeration.
 *
 * @version 4.4.1
 */
enum HtmlBootstrapColors: string
{
    case DANGER = '#DC3545';
    case DARK = '#343A40';
    case INFO = '#17A2B8';
    case LIGHT = '#F8F9FA';
    case PRIMARY = '#007BFF';
    case SECONDARY = '#6C757D';
    case SUCCESS = '#28A745';
    case WARNING = '#FFC107';

    /**
     * Apply this draw color to the given document.
     *
     * @throws \RuntimeException if the color can not be created
     *
     * @see HtmlBootstrapColors::getDrawColor()
     */
    public function applyDrawColor(PdfDocument $doc): void
    {
        $this->getDrawColor()->apply($doc);
    }

    /**
     * Apply this fill color to the given document.
     *
     * @throws \RuntimeException if the color can not be created
     *
     * @see HtmlBootstrapColors::getFillColor()
     */
    public function applyFillColor(PdfDocument $doc): void
    {
        $this->getFillColor()->apply($doc);
    }

    /**
     * Apply this text color to the given document.
     *
     * @throws \RuntimeException if the color can not be created
     *
     * @see HtmlBootstrapColors::getTextColor()
     */
    public function applyTextColor(PdfDocument $doc): void
    {
        $this->getTextColor()->apply($doc);
    }

    /**
     * Gets this value as draw color.
     *
     * @throws \RuntimeException if the color can not be created
     */
    public function getDrawColor(): PdfDrawColor
    {
        $color = PdfDrawColor::create($this->value);
        if (!$color instanceof PdfDrawColor) {
            throw new \RuntimeException('Unable to create draw color.');
        }

        return $color;
    }

    /**
     * Gets this value as fill color.
     *
     * @throws \RuntimeException if the color can not be created
     */
    public function getFillColor(): PdfFillColor
    {
        $color = PdfFillColor::create($this->value);
        if (!$color instanceof PdfFillColor) {
            throw new \RuntimeException('Unable to create fill color.');
        }

        return $color;
    }

    /**
     * Gets this value for PHP Spreadsheet or PHP Word.
     */
    public function getPhpOfficeColor(): string
    {
        return \substr($this->value, 1);
    }

    /**
     * Gets this value as text color.
     *
     * @throws \RuntimeException if the color can not be created
     */
    public function getTextColor(): PdfTextColor
    {
        $color = PdfTextColor::create($this->value);
        if (!$color instanceof PdfTextColor) {
            throw new \RuntimeException('Unable to create text color.');
        }

        return $color;
    }

    /**
     * Gets the border color for the given class name.
     */
    public static function parseBorderColor(string $class): ?PdfDrawColor
    {
        $color = match ($class) {
            'border-primary' => HtmlBootstrapColors::PRIMARY,
            'border-secondary' => HtmlBootstrapColors::SECONDARY,
            'border-success' => HtmlBootstrapColors::SUCCESS,
            'border-danger' => HtmlBootstrapColors::DANGER,
            'border-warning' => HtmlBootstrapColors::WARNING,
            'border-info' => HtmlBootstrapColors::INFO,
            'border-light' => HtmlBootstrapColors::LIGHT,
            'border-dark' => HtmlBootstrapColors::DARK,
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
            'text-bg-primary' => HtmlBootstrapColors::PRIMARY,
            'bg-secondary',
            'text-bg-secondary' => HtmlBootstrapColors::SECONDARY,
            'bg-success',
            'text-bg-success' => HtmlBootstrapColors::SUCCESS,
            'bg-danger',
            'text-bg-danger' => HtmlBootstrapColors::DANGER,
            'bg-warning',
            'text-bg-warning' => HtmlBootstrapColors::WARNING,
            'bg-info',
            'text-bg-info' => HtmlBootstrapColors::INFO,
            'bg-light',
            'text-bg-light' => HtmlBootstrapColors::LIGHT,
            'bg-dark',
            'text-bg-dark' => HtmlBootstrapColors::DARK,
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
            'text-primary' => HtmlBootstrapColors::PRIMARY,
            'text-secondary' => HtmlBootstrapColors::SECONDARY,
            'text-success' => HtmlBootstrapColors::SUCCESS,
            'text-danger' => HtmlBootstrapColors::DANGER,
            'text-warning' => HtmlBootstrapColors::WARNING,
            'text-info' => HtmlBootstrapColors::INFO,
            'text-light' => HtmlBootstrapColors::LIGHT,
            'text-dark' => HtmlBootstrapColors::DARK,
            default => null,
        };

        return $color?->getTextColor();
    }
}
