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

use App\Pdf\PdfLine;
use fpdf\PdfRectangle;

/**
 * Trait to draw dash lines.
 */
trait PdfDashLineTrait
{
    /**
     * Draw a dashed rectangle.
     *
     * After this call, the dashed style and the line width are restored.
     *
     * @param float              $x      the abscissa of the upper-left corner
     * @param float              $y      the ordinate of the upper-left corner
     * @param float              $w      the width
     * @param float              $h      the height
     * @param float              $dashes the length of dashes and gaps
     * @param PdfLine|float|null $line   the line width or null to use current
     */
    public function dashedRect(
        float $x,
        float $y,
        float $w,
        float $h,
        float $dashes = 1,
        PdfLine|float|null $line = null
    ): static {
        $oldWidth = $this->lineWidth;
        if ($line instanceof PdfLine) {
            $line->apply($this);
        } elseif (\is_float($line)) {
            $this->setLineWidth($line);
        }

        $this->setDashPattern($dashes, $dashes);
        $this->rect($x, $y, $w, $h);
        $this->resetDashPattern();
        $this->setLineWidth($oldWidth);

        return $this;
    }

    /**
     * Draw a dashed rectangle.
     *
     * After this call, the dashed style and the line width are restored.
     *
     * @param PdfRectangle       $rectangle the rectangle to draw
     * @param float              $dashes    the length of dashes and gaps
     * @param PdfLine|float|null $line      the line width or null to use current
     */
    public function dashedRectangle(PdfRectangle $rectangle, float $dashes = 1, PdfLine|float|null $line = null): static
    {
        return $this->dashedRect(
            $rectangle->x,
            $rectangle->y,
            $rectangle->width,
            $rectangle->height,
            $dashes,
            $line
        );
    }

    /**
     * Reset the dash pattern.
     */
    public function resetDashPattern(): static
    {
        $this->out('[] 0 d');

        return $this;
    }

    /**
     * Set the dash pattern used to draw dashed lines or rectangles.
     *
     * @param float $dash the length of dashes
     * @param float $gap  the length of gaps
     */
    public function setDashPattern(float $dash, float $gap): static
    {
        $this->outf('[%.3F %.3F] 0 d', $dash * $this->scaleFactor, $gap * $this->scaleFactor);

        return $this;
    }
}
