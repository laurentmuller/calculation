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

use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfTableBuilder;

/**
 * Class implementing this interface handle the draw cell text event.
 */
interface PdfDrawCellTextInterface
{
    /**
     * Called when the text must be drawn within the cell.
     *
     * @param PdfTableBuilder  $builder the parent's table
     * @param int              $index   the column index
     * @param PdfRectangle     $bounds  the cell bounds
     * @param string           $text    the cell text
     * @param PdfTextAlignment $align   the text alignment
     * @param float            $height  the line height
     *
     * @return bool true if listener handle the draw function; false to call the default behavior
     */
    public function drawCellText(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string $text, PdfTextAlignment $align, float $height): bool;
}
