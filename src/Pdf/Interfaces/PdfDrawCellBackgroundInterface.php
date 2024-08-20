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

use App\Pdf\Events\PdfCellBackgroundEvent;

/**
 * Class implementing this interface handles the draw cell background event.
 */
interface PdfDrawCellBackgroundInterface
{
    /**
     * Called when a cell must be filled.
     *
     * @param PdfCellBackgroundEvent $event the event
     *
     * @return bool true if the listener handles the draw function; false to call the default behavior
     */
    public function drawCellBackground(PdfCellBackgroundEvent $event): bool;
}
