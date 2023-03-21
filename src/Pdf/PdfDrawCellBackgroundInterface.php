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
 * Class implementing this interface handle the draw cell background event.
 */
interface PdfDrawCellBackgroundInterface
{
    /**
     * Called when a cell must be filled.
     *
     * @param PdfTableBuilder $builder the parent's table
     * @param int             $index   the column index
     * @param PdfRectangle    $bounds  the cell bounds
     *
     * @return bool true if listener handle the draw function; false to call the default behavior
     */
    public function drawCellBackground(PdfTableBuilder $builder, int $index, PdfRectangle $bounds): bool;
}
