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

use App\Pdf\Interfaces\PdfCellOutputInterface;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\Interfaces\PdfColorInterface;
use fpdf\PdfDocument;
use fpdf\PdfRectangle;

/**
 * A specialized cell filling a background rectangle.
 */
class PdfBackgroundCell extends PdfCell implements PdfCellOutputInterface
{
    /**
     * @param PdfColorInterface $background the background color
     * @param ?string           $text       the cell text
     * @param positive-int      $cols       the cell columns span
     * @param ?PdfStyle         $style      the cell style
     * @param ?PdfTextAlignment $alignment  the cell alignment
     * @param string|int|null   $link       the optional cell link.
     *                                      A URL or identifier returned by the <code>addLink()</code> function.
     */
    public function __construct(
        private readonly PdfColorInterface $background,
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ) {
        parent::__construct($text, $cols, $style, $alignment, $link);
    }

    #[\Override]
    public function output(
        PdfDocument $parent,
        PdfRectangle $bounds,
        ?PdfTextAlignment $alignment = null,
        PdfMove $move = PdfMove::RIGHT
    ): void {
        $this->drawBackground($parent, clone $bounds);
        parent::output($parent, $bounds, $alignment, $move);
    }

    private function drawBackground(PdfDocument $parent, PdfRectangle $bounds): void
    {
        $margin = $parent->getCellMargin();
        $bounds = $bounds->inflateXY(-2.0 * $margin, -$margin);
        $bounds->height = \min(PdfDocument::LINE_HEIGHT - 2.0 * $margin, $bounds->height);
        $parent->setFillColor($this->background);
        $parent->rectangle($bounds, PdfRectangleStyle::BOTH);
    }
}
