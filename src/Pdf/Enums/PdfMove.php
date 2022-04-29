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

namespace App\Pdf\Enums;

/**
 * PDF move enumeration.
 */
enum PdfMove: int
{
    /*
     * Move below of the printed cell.
     */
    case BELOW = 2;
    /*
     * Move at the beginning of the next line after the cell is printed.
     * it is equivalent to the setting <b>RIGHT</b> and calling the
     * <code>PdfDocument->Ln()</code> method immediately afterwards.
     */
    case NEW_LINE = 1;
    /*
     * Move to the right position of the printed cell.
     */
    case RIGHT = 0;
}
