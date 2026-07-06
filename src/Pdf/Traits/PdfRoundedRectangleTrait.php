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

use fpdf\Enums\PdfRectangleStyle;
use fpdf\PdfDocument;
use fpdf\PdfException;
use fpdf\PdfRectangle;

/**
 * Trait to draw rounded rectangles.
 *
 * This trait is copied from https://www.fpdf.org/fr/script/script7.php.
 *
 * @phpstan-require-extends PdfDocument
 */
trait PdfRoundedRectangleTrait
{
    /**
     * Output a rounded rectangle.
     *
     * Do nothing if the radius is not positive.
     *
     * @param float             $x      the abscissa of the rectangle
     * @param float             $y      the ordinate of the rectangle
     * @param float             $width  the width of the rectangle
     * @param float             $height the height of the rectangle
     * @param float             $radius the radius of the corners
     * @param PdfRectangleStyle $style  the style of rendering
     *
     * @throws PdfException if the radius is small or equalt to zero or if is greater than half the minimum of
     *                      the width and height
     */
    public function roundedRect(
        float $x,
        float $y,
        float $width,
        float $height,
        float $radius,
        PdfRectangleStyle $style = PdfRectangleStyle::BOTH
    ): static {
        // check radius
        if ($radius <= 0.0) {
            throw PdfException::format('The radius must be positive, %s given.', $radius);
        }
        $maximum = \min($width, $height) / 2.0;
        if ($radius > $maximum) {
            throw PdfException::format('Invalid radius: %s, maximum allowed: %s.', $radius, $maximum);
        }

        $page = $this->page;
        $length = 4.0 / 3.0 * (\M_SQRT2 - 1.0);

        $this->writer->outf(
            $page,
            '%.2F %.2F m',
            $this->scale($x + $radius),
            $this->scaleY($y)
        );
        $xc = $x + $width - $radius;
        $yc = $y + $radius;
        $this->writer->outf(
            $page,
            '%.2F %.2F l',
            $this->scale($xc),
            $this->scaleY($y)
        );
        $this->outputArc($xc + $radius * $length, $yc - $radius, $xc + $radius, $yc - $radius * $length, $xc + $radius, $yc);
        $xc = $x + $width - $radius;
        $yc = $y + $height - $radius;
        $this->writer->outf(
            $page,
            '%.2F %.2F l',
            $this->scale($x + $width),
            $this->scaleY($yc)
        );
        $this->outputArc($xc + $radius, $yc + $radius * $length, $xc + $radius * $length, $yc + $radius, $xc, $yc + $radius);
        $xc = $x + $radius;
        $yc = $y + $height - $radius;
        $this->writer->outf(
            $page,
            '%.2F %.2F l',
            $this->scale($xc),
            $this->scaleY($y + $height)
        );
        $this->outputArc($xc - $radius * $length, $yc + $radius, $xc - $radius, $yc + $radius * $length, $xc - $radius, $yc);
        $xc = $x + $radius;
        $yc = $y + $radius;
        $this->writer->outf(
            $page,
            '%.2F %.2F l',
            $this->scale($x),
            $this->scaleY($yc)
        );
        $this->outputArc($xc - $radius, $yc - $radius * $length, $xc - $radius * $length, $yc - $radius, $xc, $yc - $radius);
        $this->writer->out(
            $page,
            $style->value
        );

        return $this;
    }

    /**
     * Output a rounded rectangle.
     *
     * @param PdfRectangle      $rect   the rectangle to draw
     * @param float             $radius the radius of the corners
     * @param PdfRectangleStyle $style  the style of rendering
     */
    public function roundedRectangle(
        PdfRectangle $rect,
        float $radius,
        PdfRectangleStyle $style = PdfRectangleStyle::BOTH
    ): static {
        return $this->roundedRect(
            x: $rect->x,
            y: $rect->y,
            width: $rect->width,
            height: $rect->height,
            radius: $radius,
            style: $style
        );
    }

    private function outputArc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $this->writer->outf(
            $this->page,
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $this->scale($x1),
            $this->scaleY($y1),
            $this->scale($x2),
            $this->scaleY($y2),
            $this->scale($x3),
            $this->scaleY($y3)
        );
    }
}
