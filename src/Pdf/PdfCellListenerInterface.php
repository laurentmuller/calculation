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

use App\Pdf\Enums\PdfTextAlignment;

/**
 * Class implementing this interface deals with cell drawing functions.
 *
 * @author Laurent Muller
 */
interface PdfCellListenerInterface
{
    /**
     * Called when a cell must be filled.
     *
     * @param PdfTableBuilder $builder the table builder
     * @param int             $index   the column index
     * @param PdfRectangle    $bounds  the cell bounds
     *
     * @return bool false to call the default behavior; true if listener handle the draw function
     */
    public function drawCellBackground(PdfTableBuilder $builder, int $index, PdfRectangle $bounds): bool;

    /**
     * Called when a cell border must be drawn.
     *
     * @param PdfTableBuilder $builder the table builder
     * @param int             $index   the column index
     * @param PdfRectangle    $bounds  the cell bounds
     * @param PdfBorder       $border  the border style
     *
     * @return bool false to call the default behavior; true if listener handle the draw function
     */
    public function drawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, PdfBorder $border): bool;

    /**
     * Called when the text must be drawn within the cell.
     *
     * @param PdfTableBuilder  $builder the table builder
     * @param int              $index   the column index
     * @param PdfRectangle     $bounds  the cell bounds
     * @param string           $text    the cell text
     * @param PdfTextAlignment $align   the text alignment
     * @param float            $height  the line height
     *
     * @return bool bool false to call the default behavior; true if listener handle the draw function
     */
    public function drawCellText(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string $text, PdfTextAlignment $align, float $height): bool;
}
