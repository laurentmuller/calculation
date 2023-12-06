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
enum HtmlBootstrapColor: string
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
     * @throws \InvalidArgumentException if the color can not be created
     *
     * @see HtmlBootstrapColor::getDrawColor()
     */
    public function applyDrawColor(PdfDocument $doc): void
    {
        $this->getDrawColor()->apply($doc);
    }

    /**
     * Apply this fill color to the given document.
     *
     * @throws \InvalidArgumentException if the color can not be created
     *
     * @see HtmlBootstrapColor::getFillColor()
     */
    public function applyFillColor(PdfDocument $doc): void
    {
        $this->getFillColor()->apply($doc);
    }

    /**
     * Apply this text color to the given document.
     *
     * @throws \InvalidArgumentException if the color can not be created
     *
     * @see HtmlBootstrapColor::getTextColor()
     */
    public function applyTextColor(PdfDocument $doc): void
    {
        $this->getTextColor()->apply($doc);
    }

    /**
     * Gets this value as draw color.
     *
     * @throws \InvalidArgumentException if the color can not be created
     */
    public function getDrawColor(): PdfDrawColor
    {
        $color = PdfDrawColor::create($this->value);
        if (!$color instanceof PdfDrawColor) {
            throw new \InvalidArgumentException('Unable to create draw color.');
        }

        return $color;
    }

    /**
     * Gets this value as fill color.
     *
     * @throws \InvalidArgumentException if the color can not be created
     */
    public function getFillColor(): PdfFillColor
    {
        $color = PdfFillColor::create($this->value);
        if (!$color instanceof PdfFillColor) {
            throw new \InvalidArgumentException('Unable to create fill color.');
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
     * @throws \InvalidArgumentException if the color can not be created
     */
    public function getTextColor(): PdfTextColor
    {
        $color = PdfTextColor::create($this->value);
        if (!$color instanceof PdfTextColor) {
            throw new \InvalidArgumentException('Unable to create text color.');
        }

        return $color;
    }

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
