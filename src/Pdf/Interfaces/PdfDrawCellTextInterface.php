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

use App\Pdf\Events\PdfCellTextEvent;

/**
 * Class implementing this interface handles the draw cell text event.
 */
interface PdfDrawCellTextInterface
{
    /**
     * Called when the text must be drawn within the cell.
     *
     * @param PdfCellTextEvent $event the event
     *
     * @return bool true if listener handles the draw function; false to call the default behavior
     */
    public function drawCellText(PdfCellTextEvent $event): bool;
}
