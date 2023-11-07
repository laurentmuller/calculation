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

namespace App\Pdf;

/**
 * Color used for drawing text operations.
 */
class PdfTextColor extends AbstractPdfColor
{
    public function apply(PdfDocument $doc): void
    {
        $doc->SetTextColor($this->getRed(), $this->getGreen(), $this->getBlue());
    }

    /**
     * The default draw color is black.
     */
    public static function default(): self
    {
        return self::black();
    }
}
