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

namespace App\Pdf\Interfaces;

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use fpdf\PdfDocument;

/**
 * Class implementing this interface handles colors.
 */
interface PdfColorInterface
{
    /**
     * Apply this draw color to the given document.
     *
     * @see PdfColorInterface::getDrawColor()
     */
    public function applyDrawColor(PdfDocument $doc): void;

    /**
     * Apply this fill color to the given document.
     *
     * @see PdfColorInterface::getFillColor()
     */
    public function applyFillColor(PdfDocument $doc): void;

    /**
     * Apply this text color to the given document.
     *
     * @see PdfColorInterface::getTextColor()
     */
    public function applyTextColor(PdfDocument $doc): void;

    /**
     * Gets this value converted to RGB.
     *
     * @return array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}
     */
    public function asRGB(): array;

    /**
     * Gets this value as draw color.
     */
    public function getDrawColor(): PdfDrawColor;

    /**
     * Gets this value as fill color.
     */
    public function getFillColor(): PdfFillColor;

    /**
     * Gets this value for PHP Spreadsheet or PHP Word.
     */
    public function getPhpOfficeColor(): string;

    /**
     * Gets this value as text color.
     */
    public function getTextColor(): PdfTextColor;
}
