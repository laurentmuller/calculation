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

use App\Pdf\PdfBorder;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfTableBuilder;

/**
 * Class implementing this interface handle the draw cell border event.
 */
interface PdfDrawCellBorderInterface
{
    /**
     * Called when a cell border must be drawn.
     *
     * @param PdfTableBuilder $builder the parent's table
     * @param int             $index   the column index
     * @param PdfRectangle    $bounds  the cell bounds
     * @param PdfBorder       $border  the border style
     *
     * @return bool true if listener handle the draw function; false to call the default behavior
     */
    public function drawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, PdfBorder $border): bool;
}
