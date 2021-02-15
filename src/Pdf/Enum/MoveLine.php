<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf\Enum;

use MyCLabs\Enum\Enum;

/**
 * Move line enumeration.
 *
 * @method static MoveLine BELOW()    Move below of the printed cell.
 * @method static MoveLine NEW_LINE() Move at the beginning of the next line after the cell is printed. It is equivalent to the setting <b>MoveLine::RIGHT()</b> and calling the <code>PdfDocument->Ln()</code> method immediately afterwards.
 * @method static MoveLine RIGHT()    Move to the right position of the printed cell.
 *
 * @author Laurent Muller
 */
class MoveLine extends Enum
{
    /**
     * Move below of the printed cell.
     */
    private const BELOW = 2;

    /**
     * Move at the beginning of the next line after the cell is printed.
     * It is equivalent to the setting <b>MoveLine::RIGHT()</b> and calling the
     * <code>PdfDocument->Ln()</code> method immediately afterwards.
     */
    private const NEW_LINE = 1;

    /**
     * Move to the right position of the printed cell.
     */
    private const RIGHT = 0;
}
