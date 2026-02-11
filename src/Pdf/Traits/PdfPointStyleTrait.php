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

use App\Pdf\Enums\PdfPointStyle;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\PdfDocument;
use fpdf\PdfPoint;
use fpdf\Traits\PdfEllipseTrait;
use fpdf\Traits\PdfPolygonTrait;

/**
 * Trait to compute and output shapes.
 *
 * @phpstan-require-extends PdfDocument
 *
 * @see PdfPointStyle
 */
trait PdfPointStyleTrait
{
    use PdfEllipseTrait;
    use PdfPolygonTrait;

    /**
     * Gets the point style height for the given desired height.
     *
     * @param float $height the desired height
     *
     * @return float the point style height
     */
    public function getPointStyleHeight(float $height = self::LINE_HEIGHT): float
    {
        return $height - 2.0 * $this->cellMargin;
    }

    /**
     * Gets the width for the given point style and the given desired height.
     *
     * @param PdfPointStyle $style  the point style to get width for
     * @param float         $height the desired height
     *
     * @return float the point style width
     */
    public function getPointStyleWidth(PdfPointStyle $style, float $height = self::LINE_HEIGHT): float
    {
        return match ($style) {
            PdfPointStyle::ELLIPSE,
            PdfPointStyle::RECTANGLE => $this->getPointStyleHeight($height) * 2.0,
            default => $this->getPointStyleHeight($height),
        };
    }

    /**
     * Output the given point style.
     *
     * @param PdfPointStyle   $style  the point style to output
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyle(
        PdfPointStyle $style,
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        return match ($style) {
            PdfPointStyle::CIRCLE => $this->outputPointStyleCircle($x, $y, $width, $height, $link),
            PdfPointStyle::CROSS => $this->outputPointStyleCross($x, $y, $width, $height, $link),
            PdfPointStyle::CROSS_ROTATION => $this->outputPointStyleCrossRotation($x, $y, $width, $height, $link),
            PdfPointStyle::DIAMOND => $this->outputPointStyleDiamond($x, $y, $width, $height, $link),
            PdfPointStyle::ELLIPSE => $this->outputPointStyleEllipse($x, $y, $width, $height, $link),
            PdfPointStyle::RECTANGLE,
            PdfPointStyle::SQUARE => $this->outputPointStyleRectangle($x, $y, $width, $height, $link),
            PdfPointStyle::TRIANGLE => $this->outputPointStyleTriangle($x, $y, $width, $height, $link),
        };
    }

    /**
     * Output the given point style and text.
     *
     * @param PdfPointStyle   $style the point style to output
     * @param float           $x     the abscissa
     * @param float           $y     the ordinate
     * @param PdfMove         $move  indicates where the current position should go after the call
     * @param string|int|null $link  a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleAndText(
        PdfPointStyle $style,
        float $x,
        float $y,
        string $text,
        PdfMove $move = PdfMove::RIGHT,
        string|int|null $link = null
    ): static {
        $width = $this->getPointStyleWidth($style);
        $height = $this->getPointStyleWidth($style);
        $this->outputPointStyle($style, $x, $y + $this->cellMargin, $width, $height, $link);
        $this->setXY($x + $width, $y);

        return $this->cell(
            width: $this->getStringWidth($text),
            text: $text,
            move: $move,
            link: $link
        );
    }

    /**
     * Output the circle point style.
     *
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleCircle(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        $radius = \min($width, $height) / 2.0;
        $this->circle(
            $x + $width / 2.0,
            $y + $height / 2.0,
            $radius,
            PdfRectangleStyle::BOTH
        );

        return $this->outputPointStyleLink($x, $y, $width, $height, $link);
    }

    /**
     * Output the circle point style.
     *
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleCross(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        $size = .5;
        $oldLine = $this->lineWidth;
        $this->setLineWidth($size);
        // horizontal
        $this->line(
            $x + $size / 2.0,
            $y + $width / 2.0,
            $x + $width - $size / 2.0,
            $y + $width / 2.0
        );
        // vertical
        $this->line(
            $x + $height / 2.0,
            $y + $size / 2.0,
            $x + $height / 2.0,
            $y + $height - $size / 2.0
        );
        $this->setLineWidth($oldLine);

        return $this->outputPointStyleLink($x, $y, $width, $height, $link);
    }

    /**
     * Output the cross-rotation point style.
     *
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleCrossRotation(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        $size = .5;
        $oldLine = $this->getLineWidth();
        $this->setLineWidth($size);
        $this->line(
            $x + $size,
            $y + $size,
            $x + $width - $size,
            $y + $height - $size
        );
        $this->line(
            $x + $size,
            $y + $height - $size,
            $x + $width - $size,
            $y + $size
        );
        $this->setLineWidth($oldLine);

        return $this->outputPointStyleLink($x, $y, $width, $height, $link);
    }

    /**
     * Output the diamond point style.
     *
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleDiamond(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        $points = [
            new PdfPoint($x + $width / 2.0, $y),
            new PdfPoint($x + $width, $y + $height / 2.0),
            new PdfPoint($x + $width / 2.0, $y + $height),
            new PdfPoint($x, $y + $height / 2.0),
        ];
        $this->polygon($points, PdfRectangleStyle::BOTH);

        return $this->outputPointStyleLink($x, $y, $width, $height, $link);
    }

    /**
     * Output the ellipse point style.
     *
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleEllipse(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        $rx = $width / 2.0;
        $ry = $height / 2.0;
        $this->ellipse(
            $x + $rx,
            $y + $ry,
            $rx,
            $ry,
            PdfRectangleStyle::BOTH
        );

        return $this->outputPointStyleLink($x, $y, $width, $height, $link);
    }

    /**
     * Output the rectangle point style.
     *
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleRectangle(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        $this->rect(
            $x,
            $y,
            $width,
            $height,
            PdfRectangleStyle::BOTH
        );

        return $this->outputPointStyleLink($x, $y, $width, $height, $link);
    }

    /**
     * Output the triangle point style.
     *
     * @param float           $x      the abscissa
     * @param float           $y      the ordinate
     * @param float           $width  the width
     * @param float           $height the height
     * @param string|int|null $link   a URL or an identifier returned by <code>addLink()</code>
     */
    public function outputPointStyleTriangle(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        $points = [
            new PdfPoint($x + $width / 2.0, $y),
            new PdfPoint($x + $width, $y + $height),
            new PdfPoint($x, $y + $height),
        ];
        $this->polygon($points, PdfRectangleStyle::BOTH);

        return $this->outputPointStyleLink($x, $y, $width, $height, $link);
    }

    private function outputPointStyleLink(
        float $x,
        float $y,
        float $width,
        float $height,
        string|int|null $link = null
    ): static {
        if (PdfDocument::isLink($link)) {
            return $this->link($x, $y, $width, $height, $link);
        }

        return $this;
    }
}
