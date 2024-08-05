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

namespace App\Pdf\Traits;

use App\Pdf\PdfFont;
use App\Pdf\PdfStyle;
use fpdf\PdfFontName;

/**
 * Trait for style and font.
 *
 * @psalm-require-extends \fpdf\PdfDocument
 */
trait PdfStyleTrait
{
    /**
     * Apply the given font.
     *
     * @return PdfFont the previous font
     */
    public function applyFont(PdfFont $font): PdfFont
    {
        $oldFont = $this->getCurrentFont();
        $font->apply($this);

        return $oldFont;
    }

    /**
     * Gets the current font.
     */
    public function getCurrentFont(): PdfFont
    {
        $name = PdfFontName::tryFromFamily($this->fontFamily) ?? PdfFontName::ARIAL;

        return new PdfFont($name, $this->fontSizeInPoint, $this->fontStyle);
    }

    /**
     * Reset this current style to default.
     */
    public function resetStyle(): static
    {
        PdfStyle::default()->apply($this);

        return $this;
    }
}
